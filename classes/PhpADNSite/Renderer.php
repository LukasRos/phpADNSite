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

/**
 * Renders the pages of the application.
 */
class Renderer {

	private $twig;
	private $variables;
	private $dataRetriever;

	public function __construct($config, $dataRetriever) {
		mb_internal_encoding("UTF-8");
		
		$this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem('../templates/'.$config['template']),
				array('cache' => '../tmp', 'autoescape' => false));
		$this->variables = $config['variables'];
		$this->variables['username'] = $config['username'];
		$this->dataRetriever = $dataRetriever;
	}

	private function reformatPost(Entities\Post $post) {
		$html = $post->getText();
		$meta = $post->getMeta();
		// Process Hashtags
		/*foreach ($meta['entities']['hashtags'] as $entity) {
		$entityText = mb_substr($post->getText(), $entity['pos'], $entity['len']);
		$text = str_replace($entityText, '<a itemprop="hashtag" data-hashtag-name="'.$entity['name'].'" href="/hashtag/'.$entity['name'].'">'.$entityText.'</a>', $text);
		} */
		
		// Process Links
		foreach ($meta['entities']['links'] as $entity) {
			$entityText = mb_substr($post->getText(), $entity['pos'], $entity['len']);
			$html = str_replace($entityText, '<a href="'.$entity['url'].'">'.$entityText.'</a>', $html);
		}
		
		$data = array(
				'id' => $post->getADNPostId(),
				'num_replies' => $meta['num_replies'],
				'num_stars' => $meta['num_stars'],
				'num_reposts' => $meta['num_reposts'],
				'created_at' => $post->getCreatedAt(),
				'has_thread' => ($meta['num_replies']>0 || isset($meta['reply_to'])),
				'html' => $html
		);

		return $data;
	}

	private function generateResponse($template, $data) {
		$mergedData = array_merge($data, $this->variables);
		$tt = $this->twig->loadTemplate($template);
		return $tt->render($mergedData);
	}

	/**
	 * Renders a page that displays the timeline of latest posts from the owner of this instance
	 * 
	 * @return string
	 */
	public function renderUsertimeline() {
		$postsData = $this->dataRetriever->getUserTimeline();
		$posts = array();
		if (!$postsData) die("ERROR"); // TODO: Error handling
		for ($i = 0; $i < count($postsData); $i++) {
			$text = $postsData[$i]->getText();
			if ($text[0]=='@') continue; // Filter directed posts
			$posts[] = $this->reformatPost($postsData[$i]);
		}

		return $this->generateResponse('home.twig.html', array('posts' => $posts));
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

		return $this->generateResponse('postpage.twig.html', array('post' => $this->reformatPost($post)));
	}

}