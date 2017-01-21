<?php

/*  phpADNSite
 Copyright (C) 2016 Lukas Rosenstock

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

class StorageClient implements APIClient {

  private $username = null;
  private $storage = null;

	public function configure(array $globalConfiguration, array $userConfiguration) {
    if (is_array($globalConfiguration) && isset($globalConfiguration['storage_class'])) {
      if (!in_array('PhpADNSite\Core\PostStorage', class_implements($globalConfiguration['storage_class'])))
        throw new \Exception("The storage class <".$globalConfiguration['storage_class']."> does not implement the expected interface.");
      $this->storage = new $globalConfiguration['storage_class'];
      if (isset($globalConfiguration['storage_config']))
        $this->storage->configure($globalConfiguration['storage_config']);
    } else
      throw new \Exception("Invalid storage configuration for StorageClient.");

    if (!isset($userConfiguration['username']))
      throw new \Exception("Invalid user configuration, missing 'username'.");
    $this->username = $userConfiguration['username'];
	}

  private function addMinMax($response) {
    if (count($response['data'])==0) return $response;

    return array(
      'data' => $response['data'],
      'meta' => array_merge($response['meta'], array(
        'max_id' => $response['data'][0]['id'],
        'min_id' => $response['data'][count($response['data'])-1]['id'],
      ))
    );
  }

	public function retrieveRecentPosts($count = 20) {
    return new PostPage($this->addMinMax($this->storage->getPosts($this->username,
      $count, null, null)));
	}

	public function retrieveSinglePost($id) {
	   throw new \Exception("retrieveSinglePost not yet implemented!");
	}

	public function retrievePostThread($id) {
    return new PostPage($this->addMinMax($this->storage->getPostThread($this->username,
      $id)));
	}

	public function retrievePostsWithHashtag($tag) {
    return new PostPage($this->storage->getPostsWithHashtag($this->username, $tag));
	}

	public function retrievePostsOlderThan($id, $count = 20) {
    return new PostPage($this->addMinMax($this->storage->getPosts($this->username,
      $count, $id, null)));
	}

	public function retrievePostsNewerThan($id, $count = 20) {
    return new PostPage($this->addMinMax($this->storage->getPosts($this->username,
      $count, null, $id)));
	}

	public function retrieveUser() {
		throw new \Exception("retrieveUser not yet implemented!");
	}

	public function updateUser(User $user) {
		throw new \Exception("updateUser is not supported by this client!");
	}

	public function getFollowers($count = 200) {
		throw new \Exception("getFollowers is not supported by this client!");
	}

	public function getFollowing($count = 200) {
		throw new \Exception("getFollowing is not supported by this client!");
	}

	public function createPost($post) {
		throw new \Exception("createPost is not supported by this client!");
	}

}
