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
 * Database representation of an external page.
 * @Entity @Table(name="pas_external_pages")
 **/
class ExternalPage {
	
	/** @Id @Column(type="integer") @GeneratedValue **/
	private $id;
	
	/** @Column(type="string") **/
	private $posted_url;

	/** @Column(type="string") **/
	private $final_url;

	/** @Column(type="string") **/
	private $serialized_meta;
	
	// cache field
	private $serialized_meta_array;
	
	public function getId() {
		return $this->id;
	}
	
	public function getPostedURL() {
		return $this->posted_url;
	}
	
	public function setPostedURL($posted_url) {
		$this->posted_url = $posted_url;
		return $this;
	}
	
	public function getFinalURL() {
		return $this->final_url;
	}
	
	public function setFinalURL($final_url) {
		$this->final_url = $final_url;
		return $this;
	}
	
	public function getSerializedMeta() {
		if (!$this->serialized_meta_array) {
			$this->serialized_meta_array = json_decode($this->serialized_meta, true);
		}
		return $this->serialized_meta_array;
	}

	public function setSerializedMeta($serialized_meta) {
		$this->serialized_meta_array = $serialized_meta;
		$this->serialized_meta = json_encode($serialized_meta);
		return $this;
	}
	
	public function hasMetaField($field) {
		$data = $this->getSerializedMeta();
		if (isset($data[$field])) return true;
		else return false;
	}
	
	public function getMetaField($field) {
		$data = $this->getSerializedMeta();
		if (isset($data[$field])) return $data[$field];
		else return null;
	}
		
	public function getEmbedHTML() {
		$data = $this->getSerializedMeta();
		switch ($this->getMetaField('og:type')) {
			case 'music.song':
				$mdata = $data['og:audio'][0];
				$url = parse_url($mdata['og:audio:url']);
				if ($url['scheme']=='spotify') {
					// Spotify Embedded Track
					$html = '<iframe src="https://embed.spotify.com/?uri=spotify:'.$url['path'].'" width="300" height="80" frameborder="0" allowtransparency="true"></iframe>';
				}
				break;
			case 'video':
				$vdata = $data['og:video'][0];
				// width="'.$vdata['og:video:width'].'" height="'.$vdata['og:video:height'].'"
				$html = '<embed name="player1" type="application/x-shockwave-flash" src="'.htmlspecialchars($vdata['og:video:url']).'" style="width:400pt; height:225pt;" />';
				break;
			case 'article':
				$idata = $data['og:image'][0];
				$html = '<img src="'.$idata['og:image:url'].'" class="thumbnail" /><strong>'.$data['og:title'].'</strong><br />'.@$data['og:description'];
				break;
			default:
				$html = null;
		}
		return $html;
	}
	
}
