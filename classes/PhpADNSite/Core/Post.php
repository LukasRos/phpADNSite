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
 * Class for a post for display. Contains the post payload and further meta data.
 */
class Post {
	
	private $payload;
	private $meta = array();
	private $template = 'post.twig.html';
	private $visible = true;
	private $stopProcessing = false;
	
	public function __construct(array $payload) {
		foreach ($payload as $key => $value) {
			switch ($key) {
				case "created_at":
					$payload[$key] = new \DateTime($value);
					break;
			}
		}
		$this->payload = $payload;
	}
	
	/**
	 * Returns the full post payload as an array in the format specified and returned by the app.net API.  
	 */
	public function getPayload() {
		return $this->payload;
	}
	
	/**
	 * Check if a payload field exists.
	 */
	public function has($key) {
		return isset($this->payload[$key]);
	}
	
	/**
	 * Return a single field from the post payload.
	 */
	public function get($key) {
		return @$this->payload[$key];
	}
	
	/**
	 * Set the payload of the post. Plugins can use this method to modify the content.
	 */
	public function setPayload($payload) {
		$this->payload = $payload;
	}
	
	/**
	 * Check if a meta field exists. Plugins can retrieve meta fields created by prior plugins in the chain.
	 */
	public function hasMetaField($key) {
		return isset($this->meta[$key]);
	}
	
	/**
	 * Get a meta field. Plugins can retrieve meta fields created by prior plugins in the chain.
	 */
	public function getMetaField($key) {
		return @$this->meta[$key];
	}
	
	/**
	 * Set a meta field. Plugins can specify meta fields for communicating with plugins further down the chain.
	 */
	public function setMetaField($key, $value) {
		$this->meta[$key] = $value;
	}
	
	/**
	 * Return all meta fields as array.
	 */
	public function getAllMetaFields() {
		return $this->meta;
	}
	
	/**
	 * Gets the template used for rendering the post.
	 */
	public function getTemplate() {
		return $this->template;
	}
	
	/**
	 * Sets the template used for rendering the post. Plugins can specify a custom template here.
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}
	
	/**
	 * Check if the post is visible or not. Posts are visible by default.
	 */
	public function isVisible() {
		return $this->visible;
	}
	
	/**
	 * Toggle visibility of the posts. Plugins can use this to filter posts.
	 */
	public function setVisible($visible) {
		$this->visible = $visible;
	}
	
	/**
	 * Check if processing of this post has stopped.
	 */
	public function isProcessingStopped() {
		return $this->stopProcessing;
	}
	
	/**
	 * Sets a processing stop in order to prevent plugins further down the chain from reading or modifying this post.
	 */
	public function stopProcessing() {
		$this->stopProcessing = true;
		return $this;
	}	
}