<?php

/*  phpADNSite
 Copyright (C) 2014-2015 Lukas Rosenstock

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
use Symfony\Component\HttpFoundation\Request, Symfony\Component\HttpFoundation\Response, Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use PhpADNSite\Webmention\Handler;

class Controller implements ControllerProviderInterface {

	private $client;
	private $twig;
	private $config;
	private $domain;

	public function generateResponse($template, $postData, $customPageVars = array()) {
		return $this->twig->render($template, array_merge($postData, array(
			'site_url' => 'http://'.$this->domain.'/',
			'vars' => isset($this->config['domains'][$this->domain])
				? $this->config['domains'][$this->domain]['theme_config']['variables']
				: $this->config['domains']['*']['theme_config']['variables']
		), $customPageVars));
	}

	public function initializeWithDomain($domain) {
		$this->domain = $domain;

		// Load configuration
		if (isset($this->config['domains'][$domain])) {
			$domainConfig = $this->config['domains'][$domain];
		} else
		if (isset($this->config['domains']['*'])) {
			$domainConfig = $this->config['domains']['*'];
		} else
			throw new \Exception("The domain <".$domain."> is not configured on this instance.");

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
		$page = $this->client->retrieveRecentPosts();
		foreach ($page as $post) $processor->add($post);
		return $this->generateResponse('posts.twig.html', $processor->renderForTemplate(View::STREAM), array(
			'pagination' => array(
				'older' => ($page->hasMore()) ? $page->getMinID() : null
			)
		));
	}

	public function renderPostsBefore($id) {
		$processor = new PostProcessor($this->config['plugins']);
		$page = $this->client->retrievePostsOlderThan($id);
		foreach ($page as $post) $processor->add($post);
		return $this->generateResponse('posts.twig.html', $processor->renderForTemplate(View::STREAM), array(
				'pagination' => array(
					'older' => ($page->hasMore()) ? $page->getMinID() : null,
					'newer' => $page->getMaxID()
				)
		));
	}

	public function renderPostsAfter($id) {
		$processor = new PostProcessor($this->config['plugins']);
		$page = $this->client->retrievePostsNewerThan($id);
		foreach ($page as $post) $processor->add($post);
		return $this->generateResponse('posts.twig.html', $processor->renderForTemplate(View::STREAM), array(
				'pagination' => array(
						'older' => $page->getMinID(),
						'newer' =>  ($page->hasMore()) ? $page->getMaxID() : null
				)
		));
	}

	public function renderRecentPostsRSS() {
		$processor = new PostProcessor($this->config['plugins']);
		foreach ($this->client->retrieveRecentPosts() as $post) $processor->add($post);
		return new Response($this->generateResponse('rss.twig.xml', $processor->renderForTemplate(View::STREAM)), 200, array('Content-Type' => 'application/rss+xml'));
	}

	public function renderPermalinkPage($postId) {
		$processor = new PostProcessor($this->config['plugins']);
		$posts = $this->client->retrievePostThread($postId);
		if (!$posts) throw new FileNotFoundException('/post/'.$postId);
		$originalPost = null;
		$postDirectReplies = array();
		foreach ($posts as $post) {
			if ($post->get('id')==$postId) $originalPost = $post;
			else if ($post->get('reply_to')==$postId) $postDirectReplies[] = $post;
		}

		$processor->add($originalPost);
		foreach (array_reverse($postDirectReplies) as $p) $processor->add($p);
		return $this->generateResponse('permalink.twig.html', $processor->renderForTemplate(View::PERMALINK));
	}

	public function renderPostsWithHashtag($tag) {
		if ($tag!=strtolower($tag)) {
			// for upper- or camelcase hashtags redirect to the lowercase version
			return new RedirectResponse('/tagged/'.strtolower($tag));
		}

		$processor = new PostProcessor($this->config['plugins']);
		foreach ($this->client->retrievePostsWithHashtag($tag) as $post) $processor->add($post);
		return $this->generateResponse('tagged.twig.html', $processor->renderForTemplate(View::STREAM), array('tag' => $tag));
	}

	public function renderRSS(array $posts) {
		$processor = new PostProcessor($this->config['plugins']);
		foreach ($posts as $post) $processor->add($post);
		return new Response($this->generateResponse('rss.twig.xml', $processor->renderForTemplate(View::STREAM)),
			200, array('Content-Type' => 'application/rss+xml'));
	}

	private function convertUsers($users) {
		$usersVars = array();
		foreach ($users as $u) {
			UserProcessor::processAnnotations($u);
			$usersVars[] = $u->getPayloadForTemplate();
		}
		return $usersVars;
	}

	public function renderFollowers() {
		$user = $this->client->retrieveUser();
		$users = $this->client->getFollowers();
		return $this->generateResponse('followers.twig.html',
				array('user' => $user->getPayloadForTemplate(), 'users' => $this->convertUsers($users)));
	}

	public function renderFollowing() {
		$user = $this->client->retrieveUser();
		$users = $this->client->getFollowing();
		return $this->generateResponse('following.twig.html',
				array('user' => $user->getPayloadForTemplate(), 'users' => $this->convertUsers($users)));
	}

	public function renderError(\Exception $e, $code) {
		if ($this->config['debug']==true)
			return new Response($e->getMessage(), $code, array('Content-Type' => 'text/plain'));
		else
			return new Response($this->generateResponse('404.twig.html', array()), $code);
	}

	public function setupFederation() {
		$user = $this->client->retrieveUser();
		if ($user->hasAnnotation('net.lukasrosenstock.federatedprofile')
				&& ($value = $user->getAnnotationValue('net.lukasrosenstock.federatedprofile'))
				&& $value['profile_url']=='http://'.$this->domain.'/') {
			$message = 'The domain <'.$this->domain.'> is already configured for app.net federation.';
		} else {
			if (strpos($this->domain, '.')===false) {
				$message = 'The domain <'.$this->domain.'> is a local domain and can not be configured for federation.';
			} else {
				$user->addAnnotation('net.lukasrosenstock.federatedprofile', array(
					'profile_url' => 'http://'.$this->domain.'/',
					'post_url_template' => 'http://'.$this->domain.'/post/{id}'
				));
				try {
					$this->client->updateUser($user);
					$message = 'The user profile has now been configured to use the domain <'.$this->domain.'> for app.net federation.';
				} catch (\Exception $e) {
					$message = 'The user profile could not be updated! Are you using a valid access token?!';
				}
			}

		}

		return new Response($message, 200, array('Content-Type' => 'text/plain'));
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
			return $controller->renderError($e, $code);
		});

		$controllers->get('/', function() use ($controller) {
			// Render the homepage with recent posts
			return $controller->renderRecentPosts();
		});

		$controllers->get('/rss', function() use ($controller) {
			// Render the RSS feed of recent posts
			return $controller->renderRecentPostsRSS();
		});

		$controllers->get('/tagged/{tag}/rss', function($tag) use ($controller) {
			// Render the RSS feed for a specific hashtag
			return $controller->renderRSS($this->client->retrievePostsWithHashtag($tag));
		});

		$controllers->get('/post/{postId}', function($postId) use ($controller) {
			// Render a single post
			return $controller->renderPermalinkPage($postId);
		});

		$controllers->get('/tagged/{tag}', function($tag) use ($controller) {
			// Render posts with a specific hashtag
			return $controller->renderPostsWithHashtag($tag);
		});

		$controllers->get('/posts/before/{id}', function($id) use ($controller) {
			// Render posts before ID
			return $controller->renderPostsBefore($id);
		});

		$controllers->get('/posts/after/{id}', function($id) use ($controller) {
			// Render posts after ID
			return $controller->renderPostsAfter($id);
		});

		$controllers->get('/followers', function() use ($controller) {
			// Render list of followers
			return $controller->renderFollowers();
		});

		$controllers->get('/following', function() use ($controller) {
			// Render list of followings
			return $controller->renderFollowing();
		});

		$controllers->get('/setup/federation', function() use ($controller) {
			// Check and set up federation on the user's profile
			return $controller->setupFederation();
		});

		$controllers->post('/webmention', function(Request $r) use ($controller) {
			// Handle incoming webmentions
			return Handler::handleWebmention($r, $this->domain, $this->client);
		});

		return $controllers;
	}

}
