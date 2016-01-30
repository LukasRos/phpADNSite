<?php

/*  phpADNSite
 Copyright (C) 2015-2016 Lukas Rosenstock

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

use PhpADNSite\Core\View, PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;
use PhpADNSite\Webmention\ExternalPost;

/**
 * The "WebmentionPlugin" pre-processes posts that represent externally
 * received webmentions.
 */
class WebmentionPlugin implements Plugin {

	const ANNOTATION_TYPE = 'com.indiewebcamp.webmentions-reaction';
  const ANNOTATION_TYPE_CP = 'net.app.core.crosspost';

	private $posts = array();

	public function add(Post $post) {
		$this->posts[] = $post;
	}

	public function processAll($viewType) {
		foreach ($this->posts as $post) {

			if ($post->hasAnnotation(self::ANNOTATION_TYPE)
          && $post->hasAnnotation(self::ANNOTATION_TYPE_CP)) {

        $annotation = $post->getAnnotationValue(self::ANNOTATION_TYPE);
        $crossPost = $post->getAnnotationValue(self::ANNOTATION_TYPE_CP);

        $post->setMetaField('hide_author', true);
        $post->set('canonical_url', $crossPost['canonical_url']);
        switch ($annotation['type']) {
          case ExternalPost::TYPE_LIKE:
            $post->set('html', '<span class="glyphicon glyphicon-star"></span> '.$annotation['author'].' likes this.');
            break;
          case ExternalPost::TYPE_REPOST:
            $post->set('html', '<span class="glyphicon glyphicon-retweet"></span> '.$annotation['author'].' reposted this.');
            break;
          case ExternalPost::TYPE_REPLY:
            $post->set('html', '<span class="glyphicon glyphicon-comment"></span> '.$annotation['author'].' wrote a reply to this.');
            break;
        }
      }
		}
	}

  public function configure($configuration) {
    // nothing to do, this plugin does not have any configuration
  }
}
