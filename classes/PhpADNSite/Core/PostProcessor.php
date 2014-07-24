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
		
		$user = null;
		
		foreach ($this->posts as $post) {
			if (!$user) {
				$user = $post->get('user');
				$user['description']['html'] = EntityProcessor::generateDefaultHTML($user['description']);
			}
			if (!$post->isVisible()) continue;
			
			$payload = $post->getPayload();
			if (!isset($payload['html'])) {
				$post->set('html', EntityProcessor::generateDefaultHTML($payload));
			}
			if (isset($payload['repost_of']) && !isset($payload['repost_of']['html'])) {
				$post->set('repost_of', array_merge(
					$payload['repost_of'],
					array('html' => EntityProcessor::generateDefaultHTML($payload['repost_of']))
				));
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
				'post' => $post->getPayloadForTemplate(),
				'meta' => $post->getAllMetaFields()	
			);
		}
		
		return array('user' => $user, 'posts' => $output);
	}
	
}
