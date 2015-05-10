<?php

namespace PhpADNSite\Webmention;

use Symfony\Component\HttpFoundation\Request, Symfony\Component\HttpFoundation\Response;
use PhpADNSite\Core\APIClient, PhpADNSite\Core\Post;

class Handler {

  public static function handleWebmention(Request $r, $domain, APIClient $client) {
    $source = $r->request->get('source');
    $target = $r->request->get('target');
    if (!isset($source) || !isset($target))
      return new Response('source and target parameters required.', 400);

    if (strpos($target, $domain)===false)
      return new Response('Webmention endpoint not reponsible for target.', 400);

    try {
      $externalPost = Parser::parse($source, $target);
    } catch (\Exception $e) {
      return new Response('Exception caught: '.$e->getMessage(), 500);
    }

    $text = 'Received a '.$externalPost->getType().' from '.$externalPost->getAuthorName().'.';
    $post = array(
      'text' => $text,
      'reply_to' => (int)substr($target, strrpos($target, '/')+1),
      'entities' => array(
        'links' => array(
          array(
            'pos' => 0,
            'len' => strlen($text),
            'url' => $source
          )
        )
      ),
      'annotations' => array(
        array(
          'type' => 'net.app.core.crosspost',
          'value' => array(
            'canonical_url' => $source
          )
        ),
        array(
          'type' => 'com.indiewebcamp.webmentions-reaction',
          'value' => array(
            'author' => $externalPost->getAuthorName(),
            'type' => $externalPost->getType()
          )
        )
      )
    );

    $client->createPost($post);

    return new Response('', 202);
  }

}
