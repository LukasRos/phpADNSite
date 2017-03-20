<?php

/*  phpADNSite
 Copyright (C) 2017 Lukas Rosenstock

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

namespace PhpADNSite\PostStorages;

use PhpADNSite\Core\PostStorage;

/**
 * The "ADNExportStorage" is a read-only storage that is based on
 * the export format from app.net.
 */
class ADNExportStorage implements PostStorage {

  private $data;

  public function __construct() {
    $this->basePath = realpath(__DIR__.'/../../../posts');
  }

  public function storeOrUpdatePosts(array $posts) {
    throw new \Exception("This is a read-only storage!");
  }

  public function getUser($username) {
    return null;
  }

  public function getPostThread($username, $postId) {
    $post = null;
    foreach ($this->data as $d) {
      if ((int)$d['id'] == $postId) {
        $post = $d;
        break;
      }
    }
    return array(
      'meta' => array('more' => false),
      'data' => array($post)
    );
  }

  public function getPosts($username, $count, $maxId = null, $minId = null) {
    $posts = array();
    $more = false;
    $i = 0;
    foreach ($this->data as $d) {
      $i++;
      $id = (int)$d['id'];
      if (isset($maxId)) {
        // "before" pages
        if ($id >= $maxId) continue;
        $posts[] = $d;
        if (count($posts) == $count) {
          $more = true;
          break;
        }
      } else
      if (isset($minId)) {
        // "after" pages
        if ($id == $minId) {
          $posts = array_slice($this->data, $i-$count-1, $count);#
          $more = ($i-$count-1 > 0);
          break;
        }
      } else {
        // First page
        $posts[] = $d;
        if (count($posts) >= $count) {
          $more = (count($this->data) > count($posts));
          break;
        }
      }
    }
    if (count($posts) < $count) $more = false;
    return array(
      'meta' => array('more' => $more),
      'data' => $posts
    );
  }

  public function getPostsWithHashtag($username, $tag) {
    throw new \Exception("Hashtag functionality is not yet implemented.");
  }

  public function configure($configuration) {
    if (!isset($configuration['filename']))
      throw new \Exception("'filename' must be given as configuration parameter.");
    
    $this->data = json_decode(file_get_contents($configuration['filename']), true);
  }
}
