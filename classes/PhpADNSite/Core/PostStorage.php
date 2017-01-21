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

/**
 * Generic interface for post storages.
 */
interface PostStorage {

	/**
	 * For an array of posts, check whether the posts need to be added or updated
	 * in the storage and perform necessary actions.
	 * @param $posts An array of post payloads as strings.
	 */
	public function storeOrUpdatePosts(array $posts);

	/**
	 * Get the thread for a specific post ID.
	 * @param $username App.net username
	 * @param $postId ID of the App.net post
	 * @return array
	 */
	public function getPostThread($username, $postId);

	/**
	 * Get a list of posts.
	 * @param $username App.net username
	 * @param $count Number of posts to fetch.
	 * @param $maxId If provided, starts fetching posts starting from this ID downwards.
	 * @param $minId If provided, starts fetching posts starting from this ID upwards.
	 * @return array
	 */
	 public function getPosts($username, $count, $maxId = null, $minId = null);

	 /**
	  * Get a list of posts that have a specific hashtag.
	  * @param $username App.net username
	  * @param $hashtag The tag to look up
	  */
	 public function getPostsWithHashtag($username, $tag);

	/**
	 * Apply configuration to the storage. This method is only called when
	 * the "storage_config" key is specified in the configuration for the
	 * ArchivePlugin or the respective client.
	 * @param $configuration The configuration data.
	 */
	public function configure($configuration);

}
