<?php

/*  phpADNSite - Personal Website and Post Archive powered by app.net
 Copyright (C) 2013 Lukas Rosenstock

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

namespace PhpADNSite\Entities;

use PhpADNSite\DataRetriever;

/**
 * Database representation of an app.net post created by the owner of this instance.
 * @Entity(repositoryClass="PhpADNSite\Repositories\LocalPostRepository") @Table(name="pas_local_posts")
 **/
class LocalPost {
	
	/** @Id @Column(type="integer") @GeneratedValue **/
	private $id;
	
	/** @Column(type="integer") **/
	private $adn_post_id;

	/** @Column(type="integer") **/
	private $adn_thread_id;

	/** @Column(type="datetime") **/
	private $created_at;
	
	/** @Column(type="string") **/
	private $text;
	
	/** @OneToOne(targetEntity="RemoteUser") **/
	private $reposted_from_user;
	
	/** @Column(type="boolean") **/
	private $directed = false;

	/** @Column(type="string") **/
	private $meta;
	
	/** @Column(type="datetime") **/
	private $last_updated;
	
	private $meta_array = null;
	
	public function __construct() {
		$this->last_updated = new \DateTime();
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getADNPostId() {
		return $this->adn_post_id;
	}
	
	public function getADNThreadId() {
		return $this->adn_thread_id;
	}
	
	public function getCreatedAt() {
		return $this->created_at;
	}
	
	public function getText() {
		return $this->text;
	}
	
	public function isDirected() {
		return $this->directed;
	}
	
	public function isDirectedOrReply() {
		return ($this->isDirected() || $this->getADNPostId()!=$this->getADNThreadId);
	}
	
	public function getRepostedFromUser() {
		return $this->reposted_from_user;
	}
	
	public function getLastUpdated() {
		return $this->last_updated;
	}
	
	public function needsRefresh() {
		$recentTime = new \DateTime();
		$recentTime->modify('-15minute');
		return ($recentTime > $this->last_updated);
	}
	
	public function getMeta() {
		if (!$this->meta_array) $this->meta_array = json_decode($this->meta, true);
		return $this->meta_array;
	}
	
	/**
	 * Parse and convert data from an app.net API response into the entity format.
	 * @param array $postData
	 */
	public function parseFromAPI($postData, DataRetriever $dataRetriever) {
		foreach ($postData as $key => $value) {
			switch ($key) {
				case "created_at":
					$this->created_at = new \DateTime($value);
					break;
				case "text":
					$this->text = $value;
					$this->directed = ($value[0]=='@');
					break;
				case "id":
					$this->adn_post_id = $value;
					break;
				case "thread_id":
					$this->adn_thread_id = $value;
				default:
					if (in_array($key, array('num_stars', 'num_replies', 'source',
							'num_reposts', 'entities', 'machine_only', 'annotations')))
						$this->meta_array[$key] = $value;
			}
		}
		if (isset($postData['repost_of'])) {
			$this->reposted_from_user = $dataRetriever->getRemoteUserById($postData['repost_of']['user']['id']);
			foreach ($postData['repost_of'] as $key => $value) {
				switch ($key) {
					case "created_at":
						$this->created_at = new \DateTime($value);
						break;
					case "text":
						$this->text = $value;
						$this->directed = ($value[0]=='@');
						break;
					default:
						if (in_array($key, array('entities','annotations')))
							$this->meta_array[$key] = $value;
				}
			}
		}
		$this->last_updated = new \DateTime();
		$this->meta = json_encode($this->meta_array);	
	}
	
}
