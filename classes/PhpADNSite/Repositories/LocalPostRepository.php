<?php

namespace PhpADNSite\Repositories;

use Doctrine\ORM\EntityRepository;

class LocalPostRepository extends EntityRepository {
	
	/**
	 * Get the most recent post.
	 */
	public function getMostRecentPost() {
		$query = $this->_em->createQuery('SELECT p FROM PhpADNSite\Entities\LocalPost p ORDER BY p.adn_post_id DESC');
		$query->setMaxResults(1);
		$posts = $query->execute();
		return (count($posts)==1) ? $posts[0] : null;
	}
	
	/**
	 * Get the most recent original posts in descending order.
	 * @param number $maxResults
	 */
	public function getRecentOriginalPosts($maxResults = 20) {
		$query = $this->_em->createQuery('SELECT p FROM PhpADNSite\Entities\LocalPost p WHERE p.directed = false AND p.adn_post_id = p.adn_thread_id ORDER BY p.adn_post_id DESC');
		$query->setMaxResults($maxResults);
		return $query->execute();
	}
	
	/**
	 * Get the most recent conversation posts in descending order.
	 * @param number $maxResults
	 */
	public function getRecentConversationPosts($maxResults = 20) {
		$query = $this->_em->createQuery('SELECT p FROM PhpADNSite\Entities\LocalPost p WHERE p.directed = true OR p.adn_post_id != p.adn_thread_id ORDER BY p.adn_post_id DESC');
		$query->setMaxResults($maxResults);
		return $query->execute();
	}
	
}