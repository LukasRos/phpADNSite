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

namespace PhpADNSite\Plugins;

use PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;

/**
 * The "ArchivePlugin" writes posts into a storage. This plugin needs to
 * be configured with a PostStorage implementation.
 */
class ArchivePlugin implements Plugin {

	private $posts = array();
  private $storage = null;

	public function add(Post $post) {
    if (!isset($this->storage))
      throw new \Exception("The ArchivePlugin needs a PostStorage configuration.");
		$this->posts[] = $post;
	}

	public function processAll($viewType) {
    if (!isset($this->storage))
      throw new \Exception("The ArchivePlugin needs a PostStorage configuration.");

    $this->storage->storeOrUpdatePosts($this->posts);
	}

  public function configure($configuration) {
    if (is_array($configuration) && isset($configuration['storage_class'])) {
      if (!in_array('PhpADNSite\Core\PostStorage', class_implements($configuration['storage_class'])))
        throw new \Exception("The storage class <".$configuration['storage_class']."> does not implement the expected interface.");
      $this->storage = new $configuration['storage_class'];
      if (isset($configuration['storage_config']))
        $this->storage->configure($configuration['storage_config']);
    } else
      throw new \Exception("Invalid storage configuration for ArchivePlugin.");
  }
}
