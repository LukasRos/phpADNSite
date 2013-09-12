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

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "../vendor/autoload.php";
$config = require "../config.php";

use Symfony\Component\HttpFoundation\RedirectResponse;

$site = new Silex\Application();
PhpADNSite\ConfigLoader::configure($site, $config);

$site['dataRetriever'] = new PhpADNSite\DataRetriever($site['orm.em'], $site['user']);
$site['renderer'] = new PhpADNSite\Renderer($config, $site['dataRetriever'], $site['user']);

$site->get('/', function() use ($site) {
	// Render the user's home timeline of original posts
	return $site['renderer']->renderUserTimeline();
});

$site->get('/conversations', function() use ($site) {
	// Render the user timeline of conversations
	return $site['renderer']->renderConversationTimeline();
});

$site->get('/post/{postId}', function($postId) use ($site) {
	// Render a single post
	return $site['renderer']->renderPostPage($postId);
});

$site->get('/hashtag/{tag}', function($tag) {
	return new RedirectResponse('https://alpha.app.net/hashtags/'.$tag);
});

$site->run();