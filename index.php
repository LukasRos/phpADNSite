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

date_default_timezone_set('UTC');

require_once "vendor/autoload.php";
$config = require "config.php";

if (!isset($config) || $config['debug']==true) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

$site = new Silex\Application();
$site['debug'] = $config['debug'];
$site->mount('/', new PhpADNSite\Core\Controller($config));
$site->run();
