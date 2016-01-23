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
				case "user":
					$payload[$key] = new User($value);
					break;
				case "repost_of":
					$payload[$key] = new Post($value);
					break;
				case "reposters":
				case "starred_by":
					$users = array();
					foreach ($value as $u) $users[] = new User($u);
					$payload[$key] = $users;
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
	 * Get mention entities.
	 * @return array
	 */
	public function getMentionEntities() {
		return $this->payload['entities']['mentions'];
	}

	/**
	 * Get hashtag entities.
	 * @return array
	 */
	public function getHashtagEntities() {
		return $this->payload['entities']['hashtags'];
	}

	/**
	 * Get link entities.
	 * @return array
	 */
	public function getLinkEntities() {
		return $this->payload['entities']['links'];
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
	 * Add an annotation to the post.
	 * @param string $annotationType The annotation type, typically a reverse-hostname string
	 * @param array $annotationValue The annotation value, must be an associative array.
	 */
	public function addAnnotation($annotationType, array $annotationValue) {
		if ($this->hasAnnotation($annotationType)) {
			// Update existing annotation
			for ($i = 0; $i < count($this->payload['annotations']); $i++) {
				if ($this->payload['annotations'][$i]['type']==$annotationType) {
					$this->payload['annotations'][$i]['value'] = $annotationValue;
					break;
				}
			}
		} else {
			// Add new annotation
			if (!isset($this->payload['annotations'])) $this->payload['annotations'] = array();
			$this->payload['annotations'][] = array(
				'type' => $annotationType,
				'value' => $annotationValue
			);
		}
	}

	/**
	 * Indicates whether this post is a repost of another post.
	 */
	public function isRepost() {
		return isset($this->payload['repost_of']);
	}

	/**
	 * If this post is a repost, returns the original post as a post object.
	 */
	public function getOriginalPost() {
		if (!$this->isRepost()) return null;
		return $this->payload['repost_of'];
	}

	/**
	 * Returns a filtered amount of payload fields required for rendering a post.
	 */
	public function getPayloadForTemplate($includeUser = false) {
		$output = array();

		// Copy plain fields
		$fields = array('id', 'canonical_url', 'created_at', 'text', 'html',
				'repost_of', 'num_stars', 'num_reposts',
				'num_replies', 'source');
		foreach ($fields as $f) {
			if (isset($this->payload[$f])) $output[$f] = $this->payload[$f];
		}

		$fields = array('starred_by', 'reposters');
		// Convertable fields
		foreach ($fields as $f) {
			if (isset($this->payload[$f])) {
				$converted = array();
				foreach ($this->payload[$f] as $p) $converted[] = $p->getPayloadForTemplate();
				$output[$f] = $converted;
			}
		}
		if ($includeUser) $output['user'] = $this->payload['user']->getPayloadForTemplate();
		if ($this->isRepost()) $output['repost_of'] = $this->getOriginalPost()->getPayloadForTemplate(true);

		return $output;
	}
}
