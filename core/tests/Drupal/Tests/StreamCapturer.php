<?php

declare(strict_types=1);

namespace Drupal\Tests;

// cspell:ignore datalen PSFS_FEED_ME writeable

/**
 * Captures output to a stream and stores it for retrieval.
 */
class StreamCapturer extends \php_user_filter {

  public static $cache = '';

  public function filter($in, $out, &$consumed, $closing): int {
    while ($bucket = stream_bucket_make_writeable($in)) {
      self::$cache .= $bucket->data;
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    return PSFS_FEED_ME;
  }

}
