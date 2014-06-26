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

use Silex\Application, Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request, Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class Controller implements ControllerProviderInterface {
	
	private $client;
	private $twig;
	private $config;
	private $domain;
	
	public function generateResponse($template, $postData) {
		if (!$this->twig) return $postData['message'];
		
		return $this->twig->render($template, array(
			'user' => isset($postData[0]) ? $postData[0]['post']['user'] : null,
			'posts' => $postData,
			'site_url' => 'http://'.$this->domain.'/',
			'vars' => $this->config['domains'][$this->domain]['theme_config']['variables']
		));
	}
	
	public function initializeWithDomain($domain) {
		// Load configuration
		if (!isset($this->config['domains'][$domain])) throw new \Exception("The domain <".$domain."> is not configured on this instance.");
		$this->domain = $domain;
		$domainConfig = $this->config['domains'][$domain];
		
		// Configure backend
		if (!isset($domainConfig['backend_config'])) throw new \Exception("Backend configuration for <".$domain."> not found.");
		$this->client->configure($this->config['backend']['config'], $domainConfig['backend_config']);
		
		// Configure theme
		$this->twig = new \Twig_Environment(
				new \Twig_Loader_Filesystem(__DIR__.'/../../../templates/'.$domainConfig['theme_config']['name']),
				array('cache' => null, 'autoescape' => false));
		
		
		return true;
	}
	
	public function renderRecentPosts() {
		$processor = new PostProcessor($this->config['plugins']);
		foreach ($this->client->retrieveRecentPosts() as $post) $processor->add($post);		
		return $this->generateResponse('posts.twig.html', $processor->renderForTemplate(View::STREAM));
	}
	
	public function renderRecentPostsRSS() {
		$processor = new PostProcessor($this->config['plugins']);
		foreach ($this->client->retrieveRecentPosts() as $post) $processor->add($post);
		return new Response($this->generateResponse('rss.twig.xml', $processor->renderForTemplate(View::STREAM)), 200, array('Content-Type' => 'application/rss+xml'));
	}
	
	public function renderPermalinkPage($postId) {
		$processor = new PostProcessor($this->config['plugins']);
		$post = $this->client->retrieveSinglePost($postId);
		if (!$post) throw new FileNotFoundException('/post/'.$postId); 
		$processor->add($post);
		if (!$post->isVisible()) throw new FileNotFoundException('/post/'.$postId);
		return $this->generateResponse('permalink.twig.html', $processor->renderForTemplate(View::PERMALINK));
	}
	
	/**
	 * Initialize the PhpADNSite controller
	 * @param array $config The instance configuration
	 */
	public function __construct(array $config) {
		$backendClass = $config['backend']['class'];
		if (!in_array('PhpADNSite\Core\APIClient', class_implements($backendClass))) throw new Exception('Invalid configuration: backend must implement APIClient interface.');
		$this->client = new $backendClass();
		$this->config = $config;
	}
	
	public function connect(Application $app) {
		mb_internal_encoding("UTF-8");
		$controllers = $app['controllers_factory'];

		$controller = $this;
		
		$controllers->before(function(Request $r) use ($app, $controller) {
			$controller->initializeWithDomain($r->getHost());
		});
		
		$app->error(function(\Exception $e, $code) use ($app, $controller) {
			return new Response($controller->generateResponse('404.twig.html', array('message' => $e->getMessage()), $code));
		});
	
		$controllers->get('/', function() use ($controller) {
			// Render the homepage with recent posts
			return $controller->renderRecentPosts();
		});
		
		$controllers->get('/rss', function() use ($controller) {
			// Render the RSS feed of recent posts
			return $controller->renderRecentPostsRSS();
		});
		
		$controllers->get('/post/{postId}', function($postId) use ($controller) {
			// Render a single post
			return $controller->renderPermalinkPage($postId);
		});
	
		return $controllers;
	}
	
}