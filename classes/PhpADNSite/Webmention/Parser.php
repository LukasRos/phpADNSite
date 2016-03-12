<?php

namespace PhpADNSite\Webmention;

class Parser {

  private static function parseAuthor($mf) {
    if (isset($mf['author']) && is_array($mf['author'])
        && isset($mf['author'][0]['properties']) && isset($mf['author'][0]['properties']['name'])
        && isset($mf['author'][0]['properties']['name'][0]))
      return $mf['author'][0]['properties']['name'][0];
  }

  public static function parse($extUrl, $ownUrl) {
    $mf = \Mf2\fetch($extUrl);

    $entry = null;
    foreach ($mf['items'] as $item) {
      if (in_array('h-entry', $item['type'])) {
        if (isset($entry)) throw new \Exception("Page is expected to have only a single entry.");
        $entry = $item['properties'];
      }
    }

    if (!isset($entry)) throw new \Exception("Page is expected to have an MF2-marked up entry.");

    if (isset($entry['repost-of'])) {
      // Repost
      foreach ($entry['repost-of'] as $r) {
        if (is_string($r) && $r==$ownUrl) return new ExternalPost(ExternalPost::TYPE_REPOST, $extUrl, self::parseAuthor($entry));
      }
    } else
    if (isset($entry['in-reply-to'])) {
      // Reply
      foreach ($entry['in-reply-to'] as $r) {
        if (is_string($r) && $r==$ownUrl)
          return new ExternalPost(ExternalPost::TYPE_REPLY, $extUrl, self::parseAuthor($entry));

        if (is_array($r) && isset($r['properties'])
            && isset($r['properties']['url']) && isset($r['properties']['url'])==$ownUrl)
          return new ExternalPost(ExternalPost::TYPE_REPLY, $extUrl, self::parseAuthor($entry));
      }
    } else
    if (isset($entry['like-of'])) {
      // Like
      foreach ($entry['like-of'] as $r) {
        if (is_string($r) && $r==$ownUrl)
          return new ExternalPost(ExternalPost::TYPE_LIKE, $extUrl, self::parseAuthor($entry));

        if (is_array($r) && isset($r['properties'])
            && isset($r['properties']['url']) && isset($r['properties']['url'])==$ownUrl)
          return new ExternalPost(ExternalPost::TYPE_LIKE, $extUrl, self::parseAuthor($entry));
      }
    }
    
    throw new \Exception("No related post found on the URL.");
  }

}
