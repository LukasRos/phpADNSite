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

namespace PhpADNSite\Core;

/**
 * Generic interface for filtered views.
 */
interface FilteredView {

	/**
	 * Get the path for this view.
	 * @return string
	 */
	public function getURLPath();

  /**
   * Get the human-readable name of this view.
   * @return string
   */
	 public function getDisplayName();

  /**
   * Get the filename of the TWIG template that should be used for the view
   * or null if the default template should be used.
   * @return string|null
   */
  public function getTemplateFilename();

	/**
   * Fetch the posts that should be contained in this view from the API
   * and return them.
   * @return PostPage
   */
  public function getPostPage(APIClient $client);

}
