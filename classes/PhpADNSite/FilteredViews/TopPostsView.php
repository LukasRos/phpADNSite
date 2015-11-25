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

/**
 * Generic interface for filtered views.
 */
class TopPostsView implements FilteredView {

	public function getURLPath() {
		return "top";
	}

	public function getDisplayName() {
    return "Top Posts";
  }

  public function getTemplateFilename() {
    return "topposts.twig.html";
  }

  public function getPostPage(APIClient $client) {
    $posts = $client->retrieveRecentPosts(200);

    $topPosts = array();

    foreach ($posts as $p) {
      // Calculate value: replies * 20 + reposts * 15 + stars * 10
      $value = $p->get('num_replies')*20
          + $p->get('num_reposts')*15
          + $p->get('num_stars')*10;

      // Must be at least 10 to be considered
      if ($value<10) continue;

      $p->setMetaField('topposts.value', $value);
      $p->setMetaField('topposts.temperature',
        round(35+$value*0.05, 1)."°C");
      $topPosts[] = $p;
    }

    usort($topPosts, function($a, $b) {
      return $b->getMetaField('topposts.value')
        - $a->getMetaField('topposts.value');
    });

    // Use only top 10 posts
    $top10Posts = new PostPage();
    for ($i = 1; $i <= 10; $i++)
      if (isset($topPosts[$i])) $top10Posts->add($topPosts[$i]);

    return $top10Posts;
  }

}
