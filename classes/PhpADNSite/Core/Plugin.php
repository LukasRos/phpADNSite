<?php

/*  phpADNSite
 Copyright (C) 2014-2016 Lukas Rosenstock

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
 * Generic interface for plugins.
 */
interface Plugin {

	/**
	 * Add a post for processing.
	 * @param Post $post
	 */
	public function add(Post $post);

	/**
	 * Process all added posts.
	 * @param $viewType The type of view for which items should be processed.
	 */
	public function processAll($viewType);

	/**
	 * Apply configuration to the plugin. This method is only called when
	 * the "config" key is specified in the plugin list for this plugin.
	 * @param $configuration The configuration data.
	 */
	public function configure($configuration);

}
