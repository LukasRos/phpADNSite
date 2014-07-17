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

/**
 * Class that contains a page of posts.
 */
class PostPage implements \Iterator {
	
	private $meta;
	private $posts = array();
	private $id = 0;
	
	public function __construct(array $apiResponse) {
		$this->meta = $apiResponse['meta'];
		foreach ($apiResponse['data'] as $p) $this->posts[] = new Post($p);
	}
	
	public function current() {
		return $this->posts[$this->id];
	}
	
	public function key() {
		return $this->id;
	}
	
	public function next() {
		$this->id++;
	}
	
	public function rewind() {
		$this->id = 0;
	}
	
	public function valid() {
		return ($this->id<count($this->posts));
	}
		
	public function getMaxID() {
		return $this->meta['max_id'];
	}
	
	public function getMinID() {
		return $this->meta['min_id'];
	}
	
	public function hasMore() {
		return $this->meta['more'];
	}
}