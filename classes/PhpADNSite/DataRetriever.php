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

	private $apiClient;
	private $username;
	private $em;

	public function __construct($username, EntityManager $em) {
		$this->apiClient = new Client('https://alpha-api.app.net/');
		$this->username = $username;
		$this->em = $em;
	}

	public function getUserTimeline() {
		$gr = $this->apiClient->get('stream/0/users/@'.$this->username.'/posts')->send();
		$content = json_decode($gr->getBody(), true);
		$postData = $content['data'];
		$posts = array();
		foreach ($postData as $p) {
			$post = $this->em->getRepository('PhpADNSite\Entities\Post')->findOneBy(array('adn_post_id' => $p['id']));
			if ($post) {
				// Post already found in local database
				if ($post->needsRefresh()) $post->parseFromAPI($p);
			} else {
				// Add post to database
				$post = new Entities\Post();
				$post->parseFromAPI($p);
				$this->em->persist($post);
			}
			$posts[] = $post;
		}
		$this->em->flush();
		return $posts;
	}

	public function getSinglePostById($postId) {
		$post = $this->em->getRepository('PhpADNSite\Entities\Post')->findOneBy(array('adn_post_id' => $postId));
		if ($post) {
			// Post already found in local database
			if ($post->needsRefresh()) {
				// Refresh needed?
				$response = $this->apiClient->get('stream/0/posts/'.$postId.'?include_post_annotations=1')->send();
				$content = json_decode($response->getBody(), true);
				$post->parseFromAPI($content['data']);
				$this->em->flush();
			}
			return $post;
		} else {
			// Fetch from API
			try {
				$response = $this->apiClient->get('stream/0/posts/'.$postId.'?include_post_annotations=1')->send();
				$content = json_decode($response->getBody(), true);
				// Only accept posts from site owner
				if ($content['data']['user']['username']!=$this->username) return null;

				$post = new Entities\Post();
				$post->parseFromAPI($content['data']);
				$this->em->persist($post);
				$this->em->flush();

				return $post;
			} catch (\Exception $e) {
				return null;
			}
		}
	}
}