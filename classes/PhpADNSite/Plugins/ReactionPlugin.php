<?php

/*  phpADNSite
 Copyright (C) 2015 Lukas Rosenstock

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

/**
 * The "ReactionPlugin" pre-processes posts that represent reactions
 * to external URLs on the web.
 */
class ReactionPlugin implements Plugin {

	const ANNOTATION_TYPE = 'com.indiewebcamp.reaction';

	private $posts = array();

	public function add(Post $post) {
		$this->posts[] = $post;
	}

	public function processAll($viewType) {
		foreach ($this->posts as $post) {

			if ($post->hasAnnotation(self::ANNOTATION_TYPE)) {
        if ($viewType==View::STREAM) {
          // Hide from stream
          $post->setVisible(false);
        } else {
					$annotation = $post->getAnnotationValue(self::ANNOTATION_TYPE);

					if (isset($annotation['type']) && isset($annotation['target_name'])
							&& isset($annotation['target_url'])
							&& in_array($annotation['type'], array('like'))) {

						// Use reaction template
						$post->setTemplate('reaction.twig.html');

          	// Transfer annotation fields to meta data
						$post->setMetaField('external_reaction_type', $annotation['type']);
						$post->setMetaField('external_target_name', $annotation['target_name']);
          	$post->setMetaField('external_target_url', $annotation['target_url']);
					} else {
						// Disable invalid posts
						$post->setVisible(false);
					}
        }
      }
		}
	}
}
