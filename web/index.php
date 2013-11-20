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

use Symfony\Component\HttpFoundation\Request, Symfony\Component\HttpFoundation\RedirectResponse;

$site = new Silex\Application();
$site['config'] = require "../config.php";

$site->error(function(Exception $e) use ($site) {
	if (!isset($site['renderer'])) $site['renderer'] = new PhpADNSite\Renderer($site['config']);
	
	if (get_class($e)=='PhpADNSite\Exceptions\NoLocalADNUserException') {
		return $site['renderer']->generateUnthemedResponse('notsetup.twig.html', array());
	} else {
		return $site['renderer']->generateUnthemedResponse('error.twig.html', array('message' => $e->getMessage()));
	}
});

$site->before(function(Request $r) use ($site) {
	PhpADNSite\ConfigLoader::configure($site, $site['config']);
	
	$site['dataRetriever'] = new PhpADNSite\DataRetriever($site['orm.em'], $site['user']);
	$site['renderer'] = new PhpADNSite\Renderer($site['config'], $site['dataRetriever']);
	
	if ($site['user'] && $r->getHost()!='localhost' && $r->getHost()!=$site['user']->getDomain()) {
		return new RedirectResponse('http://'.$site['user']->getDomain().$r->getPathInfo());
	}
});

$site->get('/', function() use ($site) {
	// Render the user's home timeline of original posts
	return $site['renderer']->renderUserTimeline();
});

$site->get('/rss', function() use ($site) {
	// Render the user's RSS feed of original posts
	return $site['renderer']->renderUserTimelineFeed();
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

$site->get('/setup', function(Request $r) use ($site) {
	// Start setup flow
	if ($site['user']) return new RedirectResponse('/');
	
	$url =	'https://account.app.net/oauth/authenticate?client_id='.$site['client_id']
		.	'&response_type=code&redirect_uri=http://'.$r->getHost().'/return'
		.	'&scope=stream';
	return new RedirectResponse($url);
});

$site->get('/return', function(Request $r) use ($site) {
	// Return from setup flow
	if ($site['user']) return new RedirectResponse('/');
	if (!$r->query->has('code')) throw new Exception('No code returned from authorization.');
	
	$client = new Guzzle\Http\Client('https://account.app.net/');
	$response = $client->post('/oauth/access_token', null, array(
		'client_id' => $site['client_id'],
		'client_secret' => $site['client_secret'],
		'grant_type' => 'authorization_code',
		'redirect_uri' => 'http://'.$r->getHost().'/return',
		'code' => $r->query->get('code')
	))->send();

	$data = $response->json();
	if ($data && isset($data['access_token'])) {
		$site['dataRetriever']->configureUserWithOAuthToken($data['access_token']);
		
		return new RedirectResponse('/');		
	} else throw new Exception('No access token returned from authorization.');
});

$site->run();