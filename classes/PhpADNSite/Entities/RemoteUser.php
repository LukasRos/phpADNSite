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

/**
 * Database representation of other app.net users who may have some relationship with the owner of this instance.
 * @Entity @Table(name="pas_remote_users")
 **/
class RemoteUser {
	
	/** @Id @Column(type="integer") @GeneratedValue **/
	private $id;
	
	/** @Column(type="integer") **/
	private $adn_user_id;

	/** @Column(type="string") **/
	private $username;
	
	/** @Column(type="string") **/
	private $meta;
	
	/** @Column(type="string") **/
	private $pas_profile_url;
	
	/** @Column(type="string") **/
	private $pas_post_url_template;
	
	/** @Column(type="boolean") **/
	private $is_follower = 0;
	
	/** @Column(type="boolean") **/
	private $is_following = 0;
	
	/** @Column(type="datetime") **/
	private $last_updated;

	private $meta_array = null;
	
	public function getId() {
		return $this->id;
	}
	
	public function getADNUserId() {
		return $this->adn_user_id;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function getProfileURL() {
		if (!$this->pas_profile_url) return 'https://alpha.app.net/'.$this->username;
		return $this->pas_profile_url;
	}
	
	public function getPostURLTemplate() {
		return $this->pas_post_url_template;
	}
	
	public function isFollower() {
		return $this->is_follower;
	}
	
	public function isFollowing() {
		return $this->is_following;
	}
	
	public function getLastUpdated() {
		return $this->last_updated;
	}
	
	public function needsRefresh() {
		$recentTime = new \DateTime();
		$recentTime->modify('-1hour');
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
	public function parseFromAPI($userData) {
		foreach ($userData as $key => $value) {
			switch ($key) {
				case "username":
					$this->username = $value;
					break;
				case "id":
					$this->adn_user_id = $value;
					break;
				case "verified_domain":
					$this->pas_profile_url = 'http://'.$value.'/'; // TODO: check if PAS installed
					break;
				default:
					if (in_array($key, array('avatar_image', 'name', 'annotations')))
						$this->meta_array[$key] = $value;
			}
		}
				
		$this->last_updated = new \DateTime();
		$this->meta = json_encode($this->meta_array);	
	}
	
}
