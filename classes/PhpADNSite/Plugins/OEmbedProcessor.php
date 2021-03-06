<?php

/*  phpADNSite
 Copyright (C) 2014-2016 Lukas Rosenstock

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
			if ($annotation['type']=='rich' || $annotation['type']=='video') {
				// Rich or video annotation -> embed HTML
				$post->setMetaField('embed_html', $annotation['html']);
			} else
			if ($annotation['type']=='photo') {
				if ($viewType==View::PERMALINK) {
					// include full image in permalink pages
					$post->setMetaField('img', $annotation['url']);
				} else {
					// include only thumbnail in stream
					if (isset($annotation['thumbnail_url'])) {
						$post->setMetaField('img', $annotation['thumbnail_url']);
						$post->setMetaField('img_full', $annotation['url']);
					} else {
						$post->setMetaField('img', $annotation['url']);
					}
				}
				if (isset($annotation['title'])) {
					$post->setMetaField('img_alt', $annotation['title']);
				}
				// remove link from post and set title
				if (isset($annotation['embeddable_url'])) {
					foreach ($post->getLinkEntities() as $link) {
						if (str_replace('https:', 'http:', $link['url'])
								== str_replace('https:', 'http:', $annotation['embeddable_url'])) {
							$amendment = ' ['.parse_url($link['url'], PHP_URL_HOST).']';
							$post->set('text', str_replace($link['text'].$amendment, '', $post->get('text')));
							$post->setMetaField('img_alt', $link['text']);
							break;
						}
					}
				}
			}
		}
	}

  public function configure($configuration) {
    // nothing to do, this plugin does not have any configuration
  }
}
