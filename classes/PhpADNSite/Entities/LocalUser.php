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
 * Database representation of the owner of this instance.
 * @Entity @Table(name="pas_local_user")
 **/
class LocalUser {
	
	/** @Id @Column(type="integer") **/
	private $adn_user_id;

	/** @Column(type="string") **/
	private $username;
	
	/** @Column(type="string") **/
	private $meta;
	
	/** @Column(type="string", length=200) **/
	private $bearer_access_token;
	
	/** @Column(type="datetime") **/
	private $profile_last_updated;
	
	/** @Column(type="datetime") **/
	private $stream_last_updated;
	
	private $meta_array = null;
	
	public function __construct($bearer_access_token) {
		$this->bearer_access_token = $bearer_access_token;
	}
	
	public function getADNUserId() {
		return $this->adn_user_id;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function getBearerAccessToken() {
		return $this->bearer_access_token;
	}
	
	public function getProfileLastUpdated() {
		return $this->profile_last_updated;
	}
	
	public function getStreamLastUpdated() {
		return $this->stream_last_updated;
	}
	
	public function profileNeedsRefresh() {
		$recentTime = new \DateTime();
		$recentTime->modify('-1hour');
		return ($recentTime > $this->profile_last_updated);
	}
	
	public function streamNeedsRefresh() {
		$recentTime = new \DateTime();
		$recentTime->modify('-5minute');
		return ($recentTime > $this->stream_last_updated);
	}

	public function setStreamRefreshed() {
		$this->stream_last_updated = new \DateTime();
	}
	
	public function getMeta() {
		if (!$this->meta_array) $this->meta_array = json_decode($this->meta, true);
		return $this->meta_array;
	}
	
	/**
	 * Parse and convert data from an app.net API response into the entity format.
	 * @param array $userData
	 */
	public function parseFromAPI($userData) {
		foreach ($userData as $key => $value) {
			switch ($key) {
				case "id":
					$this->adn_user_id = $value;
					break;
				case "username":
					$this->username = $value;
					break;
				default:
					if (in_array($key, array('avatar_image', 'name', 'annotations')))
						$this->meta_array[$key] = $value;
			}
		}
				
		$this->profile_last_updated = new \DateTime();
		$this->meta = json_encode($this->meta_array);
	}
	
}
