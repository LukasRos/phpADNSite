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

use Michelf\Markdown;
use PhpADNSite\Core\View, PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;

/**
 * The "LongpostsPlugin" displays the full content of long-form postings that
 * use the net.jazzychad.adnblog.post annotation. It does so by using a custom
 * template.
 */
class LongpostsPlugin implements Plugin {

	const ANNOTATION_TYPE = 'net.jazzychad.adnblog.post';
	const TRUNCATED_PARAGRAPH_COUNT = 2;

	private $posts = array();

	public function add(Post $post) {
		$this->posts[] = $post;
	}

	public function processAll($viewType) {
		foreach ($this->posts as $post) {
			if (!$post->isRepost() && $post->hasAnnotation(self::ANNOTATION_TYPE)) {
				// Use longpost template
				$post->setTemplate('longpost.twig.html');

				// Convert longpost annotation to HTML and move to meta for access from the template
				$annotation = $post->getAnnotationValue(self::ANNOTATION_TYPE);
				$post->setMetaField('title', $annotation['title']);
				$post->setMetaField('truncated', false);

				$bodyLines = explode("\n", $annotation['body']);
				if ($viewType==View::PERMALINK) {
					$body = $annotation['body'];
				} else {
					$body = '';

					$i = 0;
					foreach ($bodyLines as $l) {
						if (trim($l)!='') {
							$body .= $l."\n\n";
							$i++;
							if ($i>=self::TRUNCATED_PARAGRAPH_COUNT) {
								$post->setMetaField('truncated', true);
								break;
							}
						}
					}
				}
				$post->set('html', Markdown::defaultTransform($body));
				$post->setMetaField('description', $bodyLines[0]);

				// Do not handle other annotations
				$post->stopProcessing();
			}
		}
	}

  public function configure($configuration) {
    // nothing to do, this plugin does not have any configuration
  }
}
