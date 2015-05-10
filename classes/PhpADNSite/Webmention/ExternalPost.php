<?php

namespace PhpADNSite\Webmention;

class ExternalPost {

  const TYPE_REPLY = 'reply';
  const TYPE_LIKE = 'like';
  const TYPE_REPOST = 'repost';

  private $type;
  private $extUrl;
  private $authorName;

  public function __construct($type, $extUrl, $authorName) {
    $this->type = $type;
    $this->extUrl = $extUrl;
    $this->authorName = $authorName;
  }

  public function getType() {
    return $this->type;
  }

  public function getExtUrl() {
    return $this->extUrl;
  }

  public function getAuthorName() {
    return $this->authorName;
  }

}
