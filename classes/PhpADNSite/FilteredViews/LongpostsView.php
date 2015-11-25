<?php

/*  phpADNSite
 Copyright (C) 2015 Lukas Rosenstock

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

namespace PhpADNSite\FilteredViews;

use PhpADNSite\Core\FilteredView, PhpADNSite\Core\APIClient, PhpADNSite\Core\PostPage;
use PhpADNSite\Plugins\LongpostsPlugin;

/**
 * Generic interface for filtered views.
 */
class LongpostsView implements FilteredView {

	public function getURLPath() {
		return "longposts";
	}

	public function getDisplayName() {
    return "Longposts";
  }

  public function getTemplateFilename() {
    return "posts.twig.html";
  }

  public function getPostPage(APIClient $client) {
    $posts = $client->retrievePostsWithHashtag('adnblog');

    $longposts = new PostPage();

    foreach ($posts as $p) {
			if ($p->hasAnnotation(LongpostsPlugin::ANNOTATION_TYPE)) $longposts->add($p);
		}

    return $longposts;
  }

}
