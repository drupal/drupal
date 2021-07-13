<?php

namespace Drupal\Tests;

/**
 * Captures output to a stream and stores it for retrieval.
 */
class StreamCapturer extends \php_user_filter {

  public static $cache = '';

  #[\ReturnTypeWillChange]
  public function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      self::$cache .= $bucket->data;
      // cSpell:disable-next-line
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    // cSpell:disable-next-line
    return PSFS_FEED_ME;
  }

}
