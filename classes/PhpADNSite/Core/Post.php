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
	
	private $repost = null;
	
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
	 * Update a single field in the post payload.
	 */
	public function set($key, $value) {
		$this->payload[$key] = $value;
		return $this;
	}
	
	/**
	 * Set the payload of the post. Plugins can use this method to modify the content.
	 */
	public function setPayload($payload) {
		$this->payload = $payload;
		return $this;
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
		return $this;
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
		return $this;
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
		return $this;
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
	
	/**
	 * Checks whether this post has at least one annotation with the given type.
	 * @param string $annotationType The annotation type, typically a reverse-hostname string
	 */
	public function hasAnnotation($annotationType) {
		if (isset($this->payload['annotations'])) {
			foreach ($this->payload['annotations'] as $annotation) {
				if ($annotation['type']==$annotationType) return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns the value of the first annotation with the given type.
	 * @param string $annotationType The annotation type, typically a reverse-hostname string
	 */
	public function getAnnotationValue($annotationType) {
		if (isset($this->payload['annotations'])) {
			foreach ($this->payload['annotations'] as $annotation) {
				if ($annotation['type']==$annotationType) return $annotation['value'];
			}
		}
		return null;
	}
	
	/**
	 * Returns the values of all annotations with the given type.
	 * @param string $annotationType The annotation type, typically a reverse-hostname string
	 */
	public function getAnnotationValues($annotationType) {
		$annotations = array();
		if (isset($this->payload['annotations'])) {
			foreach ($this->payload['annotations'] as $annotation) {
				if ($annotation['type']==$annotationType) $annotations[] = $annotation['value'];
			}
		}
		return $annotations;
	}
	
	/**
	 * Indicates whether this post is a repost of another post.
	 */
	public function isRepost() {
		return isset($this->payload['repost_of']);
	}
	
	/**
	 * If this post is a repost, returns the original post as a post object. Note that currently
	 * this is a copy as changes to the repost object will not affect template rendering. 
	 */
	public function getOriginalPost() {
		if (!$this->isRepost()) return null;
		
		if (!$this->repost) $this->repost = new Post($this->payload['repost_of']);
		return $this->repost;
	}
}