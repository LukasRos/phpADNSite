<?php

/*  phpADNSite
 Copyright (C) 2014-2015 Lukas Rosenstock

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

	const DEFAULT_STREAM_FETCH_VARS = 'include_deleted=0&include_directed=0&include_annotations=1&include_html=0';

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

	public function retrieveRecentPosts($count = 20) {
		return new PostPage($this->client
				->get('users/@'.$this->username.'/posts?count='.$count.'&'.self::DEFAULT_STREAM_FETCH_VARS)
				->send()->json());
	}

	public function retrieveSinglePost($id) {
		$request = $this->client->get('posts/'.$id.'?include_annotations=1&include_html=0&include_starred_by=1&include_reposters=1');
		$response = $request->send()->json();
		$post = new Post($response['data']);
		if ($response['data']['user']['username']!=$this->username) $post->setVisible(false);
		return $post;
	}

	public function retrievePostThread($id) {
		$response = $this->client
				->get('posts/'.$id.'/replies?include_deleted=1&include_annotations=1&include_html=0&include_starred_by=1&include_reposters=1&count=200')
				->send()->json();
		foreach ($response['data'] as $post) {
			if ($post['id']==$id && $post['user']['username']!=$this->username) return null;
		}
		return new PostPage($response);
	}

	public function retrievePostsWithHashtag($tag) {
		$request = $this->client->get('posts/search?creator_id='.$this->getIdFromUsername().'&hashtags='.$tag.'&include_deleted=0&include_directed=0&include_annotations=1&include_html=0');
		$response = $request->send()->json();
		$posts = array();
		foreach ($response['data'] as $p) $posts[] = new Post($p);
		return $posts;
	}

	public function retrievePostsOlderThan($id, $count = 20) {
		return new PostPage($this->client
				->get('users/@'.$this->username.'/posts?before_id='.$id.'&count='.$count.'&'.self::DEFAULT_STREAM_FETCH_VARS)
				->send()->json());
			}

	public function retrievePostsNewerThan($id, $count = 20) {
		return new PostPage($this->client
				->get('users/@'.$this->username.'/posts?since_id='.$id.'&count=-'.$count.'&'.self::DEFAULT_STREAM_FETCH_VARS)
				->send()->json());
	}

	public function retrieveUser() {
		$response = $this->client
			->get('users/@'.$this->username.'?include_annotations=1')
			->send()->json();
		return new User($response['data']);
	}

	public function updateUser(User $user) {
		$this->client->put('users/@'.$this->username.'?include_annotations=1',
				array('Content-Type' => 'application/json'),
				json_encode($user->getPayloadForUpdate()))->send();
	}

	public function getFollowers($count = 200) {
		return new UserPage($this->client
				->get('users/@'.$this->username.'/followers?count='.$count.'&include_annotations=1')
				->send()->json());
	}

	public function getFollowing($count = 200) {
		return new UserPage($this->client
				->get('users/@'.$this->username.'/following?count='.$count.'&include_annotations=1')
				->send()->json());
	}

	public function createPost($post) {
		return new Post($this->client->post('/posts', array('Content-Type' => 'application/json'), json_encode($post))
			->send()->json());
	}

}
