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

namespace PhpADNSite\Plugins;

use PhpADNSite\Core\View, PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;

/**
 * The "OEmbedProcessor" reads OEmbed annotations from posts and converts them into meta data that
 * can be used from templates more easily.
 */
class OEmbedProcessor implements Plugin {
	
	const ANNOTATION_TYPE = 'net.app.core.oembed';
	
	private $posts = array();
	
	public function add(Post $post) {
		$this->posts[] = $post;
	}
	
	public function processAll($viewType) {
		foreach ($this->posts as $post) {
			
			if ($post->isRepost())
				$originalPost = $post->getOriginalPost();
			else
				$originalPost = $post;
			
			if (!$originalPost->hasAnnotation(self::ANNOTATION_TYPE)) continue;
			
			$annotation = $originalPost->getAnnotationValue(self::ANNOTATION_TYPE);
			if ($annotation['type']=='photo') {
				if ($viewType==View::PERMALINK) {
					// include full image in permalink pages
					$post->setMetaField('img', $annotation['url']);
				} else {
					// include only thumbnail in stream
					$post->setMetaField('img', $annotation['thumbnail_url']);
				}
			}
		}
	}
}