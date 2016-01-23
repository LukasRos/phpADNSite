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

use PhpADNSite\Core\View, PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;
use Guzzle\Http\Client;

/**
 * The "EmbedLinkPlugin" converts links from posts into embeds by adding OEmbed
 * code to them. Currently works only for Tweets from Twitter.
 * This plugin must be included in the plugin chain BEFORE the "OEmbedProcessor".
 */
class EmbedLinkPlugin implements Plugin {

  const ANNOTATION_TYPE = 'net.app.core.oembed';

	private $posts = array();

  private $oembedClient;
  private $oembedConfig = array();

  public function __construct() {
    $this->oembedConfig = array(
      'twitter.com' => 'https://api.twitter.com/1/statuses/oembed.json'
    );
    $this->oembedClient = new Client;
  }

	public function add(Post $post) {
		$this->posts[] = $post;
	}

	public function processAll($viewType) {
    $requests = array();
		foreach ($this->posts as $post) {

      // Do not process reposts
			if ($post->isRepost()) continue;

      // Do not process if the post already has an OEmbed annotation
      if ($post->hasAnnotation(self::ANNOTATION_TYPE)) continue;

      // Check for links in post
      $links = $post->getLinkEntities();
      foreach ($links as $l) {
        $host = parse_url($l['url'], PHP_URL_HOST);
        if (isset($this->oembedConfig[$host])) {
          $requests[$post->get('id')]
            = $this->oembedClient->get($this->oembedConfig[$host]
              .'?url='.urlencode($l['url']));
          break;
        }
      }
    }

    if (count($requests)>0) {
      // Make oEmbed requests
      $this->oembedClient->send($requests);

      // Process results
      foreach ($this->posts as $post) {
        if (isset($requests[$post->get('id')])
            && $response = $requests[$post->get('id')]->getResponse()) {
          // Add OEmbed annotation
          $post->addAnnotation(self::ANNOTATION_TYPE, $response->json());
        }
      }
    }
	}
}
