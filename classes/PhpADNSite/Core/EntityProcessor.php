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

class EntityProcessor {

	public static function generateDefaultHTML($payload) {
		$html = htmlentities($payload['text'], 0, 'UTF-8');

		// Process Hashtags
		foreach ($payload['entities']['hashtags'] as $entity) {
			$entityText = htmlentities(mb_substr($payload['text'], $entity['pos'], $entity['len'], 'UTF-8'), 0, 'UTF-8');
			$html = preg_replace('/'.$entityText.'\b/', '<a itemprop="hashtag" data-hashtag-name="'.$entity['name'].'" rel="tag" href="/tagged/'.$entity['name'].'">'.$entityText.'</a>', $html, 1);
		}

		// Process Links
		$processed = array();
		foreach ($payload['entities']['links'] as $entity) {
			$entityText = htmlentities(mb_substr($payload['text'], $entity['pos'], $entity['len']), 0, 'UTF-8');
			if (in_array($entityText, $processed)) continue;
			$processed[] = $entityText;
			if (isset($entity['amended_len'])) {
				$amendment = ' ['.parse_url($entity['url'], PHP_URL_HOST).']';
				$html = str_replace($entityText.$amendment, '<a href="'.htmlspecialchars($entity['url']).'">'.$entityText.'</a>'.$amendment, $html);
			} else {
				$html = str_replace($entityText, '<a href="'.htmlspecialchars($entity['url']).'">'.$entityText.'</a>', $html);
			}
		}

		// Process User Mentions
		foreach ($payload['entities']['mentions'] as $entity) {
			//$userUrl = isset($entity['x_user_url']) ? $entity['x_user_url'] : '/redirectToUser/'.$entity['name'];
			$userUrl = 'https://alpha.app.net/'.$entity['name'];
			$entityText = mb_substr($payload['text'], $entity['pos'], $entity['len']);
			$html = preg_replace('/'.$entityText.'\b/', '<a href="'.$userUrl.'">'.$entityText.'</a>', $html, 1);
		}

		return str_replace("\n", '<br />', $html);
	}
}
