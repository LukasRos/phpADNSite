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

namespace PhpADNSite\Core;

class PostProcessor {

	private $posts = array();
	private $plugins = array();
		
	public function __construct(array $plugins) {
		foreach ($plugins as $plugin) {
			if (!in_array('PhpADNSite\Core\Plugin',class_implements($plugin))) throw new \Exception("The plugin class <".$plugin."> does not implement the expected interface.");
			$this->plugins[] = new $plugin;
		}
	}
	
	public function add(Post $post) {
		$this->posts[] = $post;
	}
	
	private function generateDefaultHTML($payload) {
		$html = $payload['text'];
		$tags = array();
			
		// Process Hashtags
		foreach ($payload['entities']['hashtags'] as $entity) {
			$entityText = mb_substr($payload['text'], $entity['pos'], $entity['len']);
			$html = preg_replace('/'.$entityText.'\b/', '<a itemprop="hashtag" data-hashtag-name="'.$entity['name'].'" rel="tag" href="/tagged/'.$entity['name'].'">'.$entityText.'</a>', $html);
			$tags[] = $entity['name'];
		}
			
		// Process Links
		foreach ($payload['entities']['links'] as $entity) {
			$entityText = mb_substr($payload['text'], $entity['pos'], $entity['len']);
			$charAfterText = mb_substr($payload['text'], $entity['pos']+$entity['len'], 1);
			$html = str_replace($entityText, '<a href="'.htmlspecialchars($entity['url']).'">'.$entityText.'</a>', $html);
		}
			
		// Process User Mentions
		foreach ($payload['entities']['mentions'] as $entity) {
			//$userUrl = isset($entity['x_user_url']) ? $entity['x_user_url'] : '/redirectToUser/'.$entity['name'];
			$userUrl = 'https://alpha.app.net/'.$entity['name'];
			$entityText = mb_substr($payload['text'], $entity['pos'], $entity['len']);
			$html = preg_replace('/'.$entityText.'\b/', '<a href="'.$userUrl.'">'.$entityText.'</a>', $html);
		}
			
		return str_replace("\n", '<br />', $html);
	}
	
	private function truncate($text, $length) {
		// truncate a string only at a whitespace (by nogdog)
		// taken (modified) from: http://stackoverflow.com/a/972031
   		if (strlen($text) > $length) {
      		$text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1 ...', $text);
   		}
   		return $text;
	}
	
	public function renderForTemplate($viewType) {
		$output = array();
		
		// Call all registered plugins
		foreach ($this->plugins as $plugin) {
			foreach ($this->posts as $p) {
				if (!$p->isProcessingStopped()) $plugin->add($p);
			}
			$plugin->processAll($viewType);
		}
		
		foreach ($this->posts as $post) {
			if (!$post->isVisible()) continue;
			
			$payload = $post->getPayload();
			if (!isset($payload['html'])) {
				$payload['html'] = $this->generateDefaultHTML($payload);
			}
			if (isset($payload['repost_of']) && !isset($payload['repost_of']['html'])) {
				$payload['repost_of']['html'] = $this->generateDefaultHTML($payload['repost_of']);
			}
			
			if (!$post->hasMetaField('title')) {
				// Generate a title for listings, e.g. in an RSS feed
				if (isset($payload['entities']['links']) && count($payload['entities']['links'])>0
						&& $payload['entities']['links'][0]['pos']<80)
					$post->setMetaField('title', substr($payload['text'], 0, $payload['entities']['links'][0]['pos']-1));
				else $post->setMetaField('title', $this->truncate($payload['text'], 80));
			}
			
			$output[] = array(
				'template' => $post->getTemplate(),
				'post' => $payload,
				'meta' => $post->getAllMetaFields()	
			);
		}
		return $output;
	}
	
}
