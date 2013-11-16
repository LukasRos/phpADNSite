<?php

/*  phpADNSite - Personal Website and Post Archive powered by app.net
 Copyright (C) 2013 Lukas Rosenstock

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace PhpADNSite;

use Guzzle\Http\Client;
use Doctrine\ORM\EntityManager;
use Opengraph\Reader;

/**
 * Retrieves data such as posts from either the local database or the app.net API.
 */
class DataRetriever {

	private $em;
	private $user;
	private $apiClient;

	public function __construct(EntityManager $em, Entities\LocalUser $user = null) {
		$this->em = $em;
		$this->user = $user;

		// Initialize Client
		$this->apiClient = new Client('https://alpha-api.app.net/stream/0/');
		// Add Authentication if available
		if ($this->user->getBearerAccessToken()) $this->apiClient->setDefaultHeaders(array(
			'Authorization' => 'Bearer '.$this->user->getBearerAccessToken()
		));
		
		// Refresh profile is required
		if ($this->user->profileNeedsRefresh()) {
			$response = $this->apiClient->get('users/'.$this->user->getADNUserId().'?include_user_annotations=1')->send();
			$content = json_decode($response->getBody(), true);
			
			$this->user->parseFromAPI($content['data']);
			$this->em->flush();
		}
	}

	public function getUser() {
		return $this->user;
	}

	private function getRemoteUser($query, $apiString) {
		$user = $this->em->getRepository('PhpADNSite\Entities\RemoteUser')->findOneBy($query);
		if ($user) {
			if ($user->needsRefresh()) {
				$response = $this->apiClient->get('users/'.$apiString.'?include_user_annotations=1')->send();
				$content = json_decode($response->getBody(), true);

				$user->parseFromAPI($content['data']);
			}
		} else {
			// Fetch from API
			try {
				$response = $this->apiClient->get('users/'.$apiString.'?include_user_annotations=1')->send();
				$content = json_decode($response->getBody(), true);

				$user = new Entities\RemoteUser();
				$user->parseFromAPI($content['data']);
				$this->em->persist($user);
			} catch (\Exception $e) {
				$user = null;
			}
		}
		$this->em->flush();
		return $user;
	}

	/**
	 * Get an app.net user by their username.
	 * @param string $userId
	 * @return Ambigous <NULL, \PhpADNSite\Entities\RemoteUser>
	 */
	public function getRemoteUserByName($userName) {
		return $this->getRemoteUser(array('username' => $userName), '@'.$userName);
	}

	/**
	 * Get an app.net user by their ID.
	 * @param integer $userId
	 * @return Ambigous <NULL, \PhpADNSite\Entities\RemoteUser>
	 */
	public function getRemoteUserById($userId) {
		return $this->getRemoteUser(array('adn_user_id' => $userId), $userId);
	}

	private function refreshStream() {
		$post = $this->em->getRepository('PhpADNSite\Entities\LocalPost')->getMostRecentPost();
		$postId = $post ? $post->getADNPostId() : 0;

		$newPostResponse = $this->apiClient->get('users/'.$this->user->getADNUserId().'/posts?since_id='.$postId
				.'&count=200&include_post_annotations=1&include_html=0')->send();
		$newPostResponseData = json_decode($newPostResponse->getBody(), true);

		foreach ($newPostResponseData['data'] as $p) {
			// Add post to database
			$post = new Entities\LocalPost();
			$post->parseFromAPI($p, $this);
			$this->em->persist($post);
		}

		$this->user->setStreamRefreshed();
		$this->em->flush();
	}

	private function refreshSinglePost(Entities\LocalPost $post) {
		try {
			$response = $this->apiClient->get('posts/'.$post->getADNPostId()
					.'?include_post_annotations=1&include_html=0')->send();
			$content = json_decode($response->getBody(), true);
			$post->parseFromAPI($content['data'], $this);
			$this->em->flush();
		} catch (\Exception $e) {
			// silently ignore
		}
	}

	private function refreshMultiplePosts(array $posts) {
		if ($this->user->getBearerAccessToken()) {
			$idArray = array();
			$idPostAssoc = array();
			foreach ($posts as $p) {
				if (get_class($p)=="PhpADNSite\Entities\LocalPost") {
					$idArray[] = $p->getADNPostId();
					$idPostAssoc[$p->getADNPostId()] = $p;
				}
			}
			try {
				$response = $this->apiClient->get('posts?ids='.implode(',', $idArray)
						.'&include_post_annotations=1&include_html=0')->send();
				$content = json_decode($response->getBody(), true);
				foreach ($content['data'] as $postData) {
					$id = $postData['id'];
					$idPostAssoc[$id]->parseFromAPI($postData, $this);
				}
				$this->em->flush();
			} catch (\Exception $e) {
				// silently ignore
			}
		} else {
			// If no authentication is available, fall back to single post retrieval
			foreach ($posts as $p) $this->refreshSinglePost($p);
		}
	}

	/**
	 * Returns the user timeline which contains all original posts (no directed posts or replies) and reposts from the local instance owner.
	 * @param number $maxResults
	 * @throws Exceptions\NoLocalADNUserException
	 * @return An array of Posts
	 */
	public function getUserTimeline($maxResults = 20) {
		if (!$this->user) throw new Exceptions\NoLocalADNUserException();
		if ($this->user->streamNeedsRefresh()) $this->refreshStream();

		$posts = $this->em->getRepository('PhpADNSite\Entities\LocalPost')->getRecentOriginalPosts($maxResults);
		$postsToRefresh = array();
		foreach ($posts as $p) if ($p->needsRefresh()) $postsToRefresh[] = $p;
		if (count($postsToRefresh)>0) $this->refreshMultiplePosts($postsToRefresh);
		return $posts;
	}

	/**
	 * Returns the conversation timeline which contains all directed posts and replies from the local instance owner.
	 * @param number $maxResults
	 * @throws Exceptions\NoLocalADNUserException
	 * @return An array of Posts
	 */
	public function getConversationTimeline($maxResults = 20) {
		if (!$this->user) throw new Exceptions\NoLocalADNUserException();
		if ($this->user->streamNeedsRefresh()) $this->refreshStream();

		$posts = $this->em->getRepository('PhpADNSite\Entities\LocalPost')->getRecentConversationPosts($maxResults);
		$postsToRefresh = array();
		foreach ($posts as $p) if ($p->needsRefresh()) $postsToRefresh[] = $p;
		if (count($postsToRefresh)>0) $this->refreshMultiplePosts($postsToRefresh);
		return $posts;
	}

	/**
	 * Returns a single post from the local instance owner by its ID.
	 * @param unknown $postId
	 * @return unknown|NULL|\PhpADNSite\Entities\LocalPost
	 */
	public function getSinglePostById($postId) {
		$post = $this->em->getRepository('PhpADNSite\Entities\LocalPost')->findOneBy(array('adn_post_id' => $postId));
		if ($post) {
			// LocalPost already found in local database
			if ($post->needsRefresh()) $this->refreshSinglePost($post);
			return $post;
		} else {
			// Fetch from API
			try {
				$response = $this->apiClient->get('posts/'.$postId
						.'?include_post_annotations=1&include_html=0')->send();
				$content = json_decode($response->getBody(), true);
				// Only accept posts from instance owner
				if ($content['data']['user']['username']!=$this->username) return null;

				$post = new Entities\LocalPost();
				$post->parseFromAPI($content['data'], $this);
				$this->em->persist($post);
				$this->em->flush();

				return $post;
			} catch (\Exception $e) {
				return null;
			}
		}
	}
	
	/**
	 * Fetches meta data and an HTML embed code for the given URL (external link). 
	 * @param string $url
	 */
	public function getExternalPageData($url) {
		$externalPage = $this->em->getRepository('PhpADNSite\Entities\ExternalPage')->findOneBy(array('posted_url' => $url));
		if (!$externalPage) {
			$externalPage = new Entities\ExternalPage();
			$externalPage->setPostedURL($url);
			try {
				$client = new Client();
				$response = $client->get($url)->send();
				$externalPage->setFinalURL($response->getEffectiveUrl());
				$reader = new Reader();
				$reader->parse($response->getBody(true));
				$externalPage->setSerializedMeta($reader->getArrayCopy());
			} catch (\Exception $e) {
				return null;
			}
			$this->em->persist($externalPage);
			$this->em->flush();
		}
		
		return array(
			'url' => $externalPage->getFinalURL(),
			'title' => $externalPage->hasMetaField('og:title') ? $externalPage->getMetaField('og:title') : $externalPage->getFinalURL(), 
			'html' => $externalPage->getEmbedHTML()
		);
		
	}
}