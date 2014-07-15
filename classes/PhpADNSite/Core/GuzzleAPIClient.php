<?php

/*  phpADNSite
 Copyright (C) 2014 Lukas Rosenstock

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

namespace PhpADNSite\Core;

use Guzzle\Http\Client;

class GuzzleAPIClient implements APIClient {

	private $client;
	private $username;
		
	private $userId = null;
	
	private function getIdFromUsername() {
		if (!$this->userId) {
			$userRequest = $this->client->get('users/@'.$this->username);
			$userResponse = $userRequest->send()->json();
			$this->userId = $userResponse['data']['id'];
		}
		return $this->userId;	
	}
	
	public function configure(array $globalConfiguration, array $userConfiguration) {
		$this->client = new Client($globalConfiguration['uri']);
		$this->username = $userConfiguration['username'];
		$this->client->setDefaultHeaders(array('Authorization' => 'Bearer '.$userConfiguration['access_token']));
	}
	
	public function retrieveRecentPosts() {
		$request = $this->client->get('users/@'.$this->username.'/posts?include_deleted=0&include_directed=0&include_annotations=1&include_html=0');
		$response = $request->send()->json();
		$posts = array();
		foreach ($response['data'] as $p) $posts[] = new Post($p);
		return $posts;
	}
	
	public function retrieveSinglePost($id) {
		$request = $this->client->get('posts/'.$id.'?include_annotations=1&include_html=0&include_starred_by=1&include_reposters=1');
		$response = $request->send()->json();
		$post = new Post($response['data']);
		if ($response['data']['user']['username']!=$this->username) $post->setVisible(false);
		return $post;
	}
	
	public function retrievePostsWithHashtag($tag) {
		$request = $this->client->get('posts/search?creator_id='.$this->getIdFromUsername().'&hashtags='.$tag.'&include_deleted=0&include_directed=0&include_annotations=1&include_html=0');
		$response = $request->send()->json();
		$posts = array();
		foreach ($response['data'] as $p) $posts[] = new Post($p);
		return $posts;		
	}
	
}