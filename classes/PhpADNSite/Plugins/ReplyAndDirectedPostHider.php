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

use PhpADNSite\Core\Post, PhpADNSite\Core\Plugin;

/**
 * The "ReplyAndDirectedPostHider" hides all posts from the timeline that are replies to existing
 * threads or are directed (@). This is useful to keep a page clean of segments of conversations.
 */
class ReplyAndDirectedPostHider implements Plugin {
	
	private $posts = array();
	
	public function add(Post $post) {
		$this->posts[] = $post;
	}
	
	public function processAll() {
		foreach ($this->posts as $post) {
			$text = $post->get('text');
			if ($text[0]=='@' || $post->has('reply_to')) $post->setVisible(false);
		}
	}
}