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

namespace PhpADNSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the pages of the application.
 */
class Renderer {

	private $twig;
	private $variables;
	private $dataRetriever;

	public function __construct($config, $dataRetriever = null) {
		mb_internal_encoding("UTF-8");

		$this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem('../templates/'.$config['template']),
				array('cache' => '../tmp', 'autoescape' => false));
		
		$this->unthemedTwig = new \Twig_Environment(new \Twig_Loader_Filesystem('../pages'),
				array('cache' => '../tmp', 'autoescape' => false));
		
		$this->variables = $config['variables'];
		$this->dataRetriever = $dataRetriever;
	}

	private function reformatPost(Entities\LocalPost $post) {
		$html = $post->getText();
		$meta = $post->getMeta();
		$tags = array();
		
		// Process Hashtags
		foreach ($meta['entities']['hashtags'] as $entity) {
			$entityText = mb_substr($post->getText(), $entity['pos'], $entity['len']);
			$html = preg_replace('/'.$entityText.'\b/', '<a itemprop="hashtag" data-hashtag-name="'.$entity['name'].'" href="http://'.$this->dataRetriever->getUser()->getDomain().'/hashtag/'.$entity['name'].'">'.$entityText.'</a>', $html);
			$tags[] = $entity['name']; 
		}

		// Process Links
		foreach ($meta['entities']['links'] as $entity) {
			$entityText = mb_substr($post->getText(), $entity['pos'], $entity['len']);
			$charAfterText = mb_substr($post->getText(), $entity['pos']+$entity['len'], 1); 
			$embed = $this->dataRetriever->getExternalPageData($entity['url']);
			if ($embed && $embed['html'] && ($charAfterText=='' || $charAfterText==' ' || $charAfterText=="\n")) {
				// embedded media
				$html = str_replace($entityText, $embed['html'], $html);
			} elseif ($embed && $entityText==$entity['url']) {
				// extend shortened
				$html = str_replace($entityText, '<a href="'.htmlspecialchars($entity['url']).'" title="'.htmlspecialchars($embed['url']).'">'.$entityText.'</a>', $html);
			} else {
				// default link without meta data
				$html = str_replace($entityText, '<a href="'.htmlspecialchars($entity['url']).'">'.$entityText.'</a>', $html);
			}
		}

		// Process User Mentions
		foreach ($meta['entities']['mentions'] as $entity) {
			$user = $this->dataRetriever->getRemoteUserByName($entity['name']);
			if (!$user) continue;
			$entityText = mb_substr($post->getText(), $entity['pos'], $entity['len']);
			$html = preg_replace('/'.$entityText.'\b/', '<a href="'.$user->getProfileURL().'">'.$entityText.'</a>', $html);
		}

		$data = array(
				'id' => $post->getADNPostId(),
				'num_replies' => $meta['num_replies'],
				'num_stars' => $meta['num_stars'],
				'num_reposts' => $meta['num_reposts'],
				'created_at' => $post->getCreatedAt(),
				'has_thread' => ($meta['num_replies']>0 || isset($meta['reply_to'])),
				'tags' => $tags,
				'text' => $post->getText(),
				'html' => str_replace("\n", '<br />', $html)
		);

		if ($post->getRepostedFromUser()) {
			// Add user information in case of repost
			$user = $post->getRepostedFromUser();
			$userMeta = $user->getMeta();
				
			$data['repost'] = true;
			$data['user'] = array(
					'username' => $user->getUsername(),
					'name' => $userMeta['name'],
					'url' => $user->getProfileURL(),
					'avatar_image' => $userMeta['avatar_image']
			);
		}

		return $data;
	}
	
	private function getMasterVariables() {
		if (!$this->dataRetriever || !$this->dataRetriever->getUser()) return $this->variables;
		return array_merge($this->variables, array(
			'username' => $this->dataRetriever->getUser()->getUsername(),
			'user' => $this->dataRetriever->getUser()->getMeta(),
			'site_url' => 'http://'.$this->dataRetriever->getUser()->getDomain().'/',
			'site_title' => $this->dataRetriever->getUser()->getUsername()
		));
	}

	private function generateResponse($template, $data) {
		return $this->twig->render($template, array_merge($data, $this->getMasterVariables()));
	}
	
	public function generateUnthemedResponse($template, $data) {
		return $this->unthemedTwig->render($template, array_merge($data, $this->getMasterVariables()));
	}
	
	private function generateFeedResponse($template, $data) {
		return new Response($this->generateUnthemedResponse($template, $data), 200, array('Content-Type' => 'application/rss+xml'));
	}

	/**
	 * Renders a page that displays the timeline of latest posts from the owner of this instance.
	 *
	 * @return string
	 */
	public function renderUserTimeline() {
		$postsData = $this->dataRetriever->getUserTimeline();
		$posts = array();
		if (!$postsData) die("ERROR"); // TODO: Error handling
		for ($i = 0; $i < count($postsData); $i++) {
			$posts[] = array_merge($this->reformatPost($postsData[$i]), array(
					'firstOnDay' =>	($i==0 || $postsData[$i-1]->getCreatedAt()->format('Ymd')!=$postsData[$i]->getCreatedAt()->format('Ymd'))
			));
		}
			
		return $this->generateResponse('home.twig.html', array('posts' => $posts));
	}
	
	/**
	 * Renders the RSS feed of the latest posts from the owner of this instance.
	 *
	 * @return string
	 */
	public function renderUserTimelineFeed() {
		$postsData = $this->dataRetriever->getUserTimeline();
		$posts = array();
		if (!$postsData) die("ERROR"); // TODO: Error handling
		for ($i = 0; $i < count($postsData); $i++) {
			$posts[] = array_merge($this->reformatPost($postsData[$i]), array(
					'firstOnDay' =>	($i==0 || $postsData[$i-1]->getCreatedAt()->format('Ymd')!=$postsData[$i]->getCreatedAt()->format('Ymd'))
			));
		}
			
		return $this->generateFeedResponse('rss.twig.xml', array('posts' => $posts));
	}

	/**
	 * Renders a page that displays the timeline of latest conversation posts from the owner of this instance.
	 *
	 * @return string
	 */
	public function renderConversationTimeline() {
		$postsData = $this->dataRetriever->getConversationTimeline();
		$posts = array();
		if (!$postsData) die("ERROR"); // TODO: Error handling
		for ($i = 0; $i < count($postsData); $i++) {
			$posts[] = array_merge($this->reformatPost($postsData[$i]), array(
					'firstOnDay' =>	($i==0 || $postsData[$i-1]->getCreatedAt()->format('Ymd')!=$postsData[$i]->getCreatedAt()->format('Ymd'))
			));
		}
			
		return $this->generateResponse('conversations.twig.html', array('posts' => $posts));
	}

	/**
	 * Renders a page that displays a single post from the owner of this instance.
	 *
	 * @param integer $postId The Post ID from app.net
	 * @return string
	 */
	public function renderPostPage($postId) {
		$post = $this->dataRetriever->getSinglePostById($postId);
		if (!$post) die("ERROR"); // TODO: Error handling

		return $this->generateResponse('postpage.twig.html',
				array('post' => array_merge($this->reformatPost($post), array('firstOnDay' => true))));
	}

}