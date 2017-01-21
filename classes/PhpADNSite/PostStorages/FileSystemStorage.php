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

namespace PhpADNSite\PostStorages;

use PhpADNSite\Core\PostStorage;

/**
 * The "FileSystemStorage" archives posts into a directory structure
 * on the local file system.
 */
class FileSystemStorage implements PostStorage {

  private $datePath = 'Y/m';
  private $basePath;  

  public function __construct() {
    $this->basePath = realpath(__DIR__.'/../../../posts');
  }

  private function getIndex($username) {
    $indexFilename = $this->basePath.DIRECTORY_SEPARATOR.$username.
      DIRECTORY_SEPARATOR.'index.json';

    return file_exists($indexFilename)
      ? json_decode(file_get_contents($indexFilename), true)
      : array();
  }

  private function storeIndex($username, $index) {
    $indexFilename = $this->basePath.DIRECTORY_SEPARATOR.$username.
      DIRECTORY_SEPARATOR.'index.json';
    file_put_contents($indexFilename, json_encode($index, JSON_PRETTY_PRINT));
  }

  private function loadPost($username, $dateFormatted, $id) {
    return json_decode(file_get_contents($this->basePath.DIRECTORY_SEPARATOR.$username
        .DIRECTORY_SEPARATOR.$dateFormatted.DIRECTORY_SEPARATOR.$id.'.json'), true);
  }

  public function storeOrUpdatePosts(array $posts) {
    if (count($posts)==0) return;

    // Store or update the user profile
    $user = $posts[0]->get('user');
    $username = $user->get('username');
    $basePath = $this->basePath.DIRECTORY_SEPARATOR.$username.DIRECTORY_SEPARATOR;
    if (!is_dir($basePath)) mkdir($basePath);

    $userFilename = $basePath.'user.json';
    $userData = json_encode($user->getOriginalPayload(), JSON_PRETTY_PRINT);
    if (!file_exists($userFilename) || file_get_contents($userFilename)!=$userData)
      file_put_contents($userFilename, $userData);

    // Initialize index or load if it exists already
    $indexUpdated = false;
    $index = $this->getIndex($username);

    // Initialize hashtag lookup or load if it exists already
    $hashtagsFilename = $basePath.'hashtags.json';
    $hashtagsUpdated = false;
    if (file_exists($hashtagsFilename))
      $hashtags = json_decode(file_get_contents($hashtagsFilename), true);
    else
      $hashtags = array();

    foreach ($posts as $post) {
      if (!get_class($post)=='PhpADNSite\Core\Post')
        throw new \Exception("Invalid data for storage.");

      // Process post for storage
      $postPayload = $post->getOriginalPayload();
      unset($postPayload['user']);
      $data = json_encode($postPayload, JSON_PRETTY_PRINT);

      $dateFormatted = $post->get('created_at')->format($this->datePath);
      $filename = $basePath.$dateFormatted.DIRECTORY_SEPARATOR
        .$post->get('id').'.json';

      // Create directory if required
      if (!is_dir(dirname($filename)))
        mkdir(dirname($filename), 0755, true);

      if (!file_exists($filename) || file_get_contents($filename)!=$data) {
        // Store new or updated post in file
        file_put_contents($filename, $data);
        // Update index
        $id = (int)$post->get('id');
        if (!isset($index[$dateFormatted])) {
          // Create index for date
          $index[$dateFormatted] = array('lo' => $id, 'hi' => $id);
          $indexUpdated = true;
        } else {
          // Update index for date if required
          if ($id > $index[$dateFormatted]['hi']) {
            $index[$dateFormatted]['hi'] = $id;
            $indexUpdated = true;
          }
          if ($id < $index[$dateFormatted]['lo']) {
            $index[$dateFormatted]['lo'] = $id;
            $indexUpdated = true;
          }
        }
        // Update hashtags
        foreach ($post->getHashtagEntities() as $entity) {
          $name = $entity['name'];
          if (!isset($hashtags[$name])) {
            $hashtags[$name] = array($id);
            $hashtagsUpdated = true;
          } elseif (!in_array($id, $hashtags[$name])) {
            $hashtags[$name][] = $id;
            $hashtagsUpdated = true;
          }
        }
      }
    }

    // Store the updated index
    if ($indexUpdated == true) $this->storeIndex($username, $index);

    // Store the updated hashtags
    if ($hashtagsUpdated == true)
      file_put_contents($hashtagsFilename, json_encode($hashtags, JSON_PRETTY_PRINT));
  }

  public function getUser($username) {
    $userFilename = $this->basePath.DIRECTORY_SEPARATOR.$username.
      DIRECTORY_SEPARATOR.'user.json';

    return file_exists($userFilename)
      ? json_decode(file_get_contents($userFilename), true)
      : null;
  }

  private function getPostsForDateFormatted($username, $dateFormatted) {
    $allPostsForDate = array();
    $directory = dir($this->basePath.DIRECTORY_SEPARATOR.$username
      .DIRECTORY_SEPARATOR.$dateFormatted);
    while (false !== ($entry = $directory->read())) {
      if (strpos($entry, '.json') === false) continue;
      $allPostsForDate[] = substr($entry, 0, strpos($entry, '.'));
    }
    rsort($allPostsForDate);
    $posts = array();
    foreach ($allPostsForDate as $id)
      $posts[] = $this->loadPost($username, $dateFormatted, $id);
    return $posts;
  }

  private function getNextDateFormatted($index, $upperBound) {
    $highestDateInt = 0;
    $highestDateFormatted = "";
    $output = array();
    foreach (array_keys($index) as $date) {
      $dt = strtotime($date);
      if ($dt < $upperBound && $dt > $highestDateInt) {
        $highestDateInt = $dt;
        $highestDateFormatted = $date;
      }
    }
    return $highestDateFormatted;
  }

  private function getPostDateFormattedByID($index, $id) {
    foreach ($index as $date => $ids) {
      if ($id <= $ids['hi'] && $id >= $ids['lo'])
        return $date;
    }
    return null;
  }

  public function getPostThread($username, $postId) {
    $dateFormatted = $this->getPostDateFormattedByID($this->getIndex($username), $postId);
    $post = $this->loadPost($username, $dateFormatted, $postId);
    $post['user'] = $this->getUser($username);;
    return array(
      'meta' => array('more' => false),
      'data' => array($post)
    );
  }

  public function getPosts($username, $count, $maxId = null, $minId = null) {
    $user = $this->getUser($username);
    $index = $this->getIndex($username);

    $upperBound = isset($maxId)
      ? strtotime($this->getPostDateFormattedByID($index, $maxId))
      : PHP_INT_MAX;

    $output = array();
    while (count($output) <= $count+1) {
      // Select next day
      $dateFormatted = $this->getNextDateFormatted($index, $upperBound);
      if ($dateFormatted=="") break;
      // Fetch posts from this day
      foreach ($this->getPostsForDateFormatted($username, $dateFormatted) as $p) {
        $p['user'] = $user;
        if ((isset($maxId) && $p['id'] < $maxId)
            || (isset($minId) && $p['id'] > $minId)
            || (!isset($maxId) && !isset($minId)))
          $output[] = $p;
      }
      $upperBound = strtotime($dateFormatted);
    }
    $postsSliced = array_slice($output, 0, 20);
    return array(
      'meta' => array('more' => (count($output) > count($postsSliced))),
      'data' => $postsSliced
    );
  }

  public function getPostsWithHashtag($username, $tag) {
    $user = $this->getUser($username);
    $index = $this->getIndex($username);

    $htListFilename = $this->basePath.DIRECTORY_SEPARATOR.$username.
      DIRECTORY_SEPARATOR.'hashtags.json';

    $hashtagPosts = file_exists($htListFilename)
      ? json_decode(file_get_contents($htListFilename), true)
      : array();

    $posts = array();
    if (isset($hashtagPosts[$tag])) {
      foreach ($hashtagPosts[$tag] as $id) {
        $dateFormatted = $this->getPostDateFormattedByID($index, $id);
        if (!isset($dateFormatted)) continue;
        $posts[] = array_merge($this->loadPost($username, $dateFormatted, $id), array('user' => $user));
      }
    }
  
    return array('data' => $posts);
  }

  public function configure($configuration) {
    if (isset($configuration['base_path']))
      $this->basePath = $configuration['base_path'];
    if (isset($configuration['date_path']))
      $this->datePath = $configuration['date_path'];
  }
}
