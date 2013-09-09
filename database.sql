--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adn_post_id` bigint(20) NOT NULL,
  `adn_thread_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `text` text NOT NULL,
  `meta` text NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `adn_post_id` (`adn_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
