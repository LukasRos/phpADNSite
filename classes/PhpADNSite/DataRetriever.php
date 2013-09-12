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

		$this->apiClient = new Client('https://alpha-api.app.net/stream/0/');
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

	private function refreshPost(LocalPost $post) {
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
		foreach ($posts as $p) if ($p->needsRefresh()) $this->refreshPost($p);
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
		foreach ($posts as $p) if ($p->needsRefresh()) $this->refreshPost($p);
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
			if ($post->needsRefresh()) $this->refreshPost($post);
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
}