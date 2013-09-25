<?php

/**
 * @file
 * Contains \Drupal\Component\Uuid\Php.
 */

namespace Drupal\Component\Uuid;

use Drupal\Component\Utility\Crypt;

/**
 * Generates a UUID v4 using PHP code.
 *
 * Loosely based on Ruby's UUIDTools generate_random logic.
 *
 * @see http://uuidtools.rubyforge.org/api/classes/UUIDTools/UUID.html
 */
class Php implements UuidInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $hex = substr(hash('sha256', Crypt::randomBytes(16)), 0, 32);

    // The field names refer to RFC 4122 section 4.1.2.
    $time_low = substr($hex, 0, 8);
    $time_mid = substr($hex, 8, 4);

    $time_hi_and_version = base_convert(substr($hex, 12, 4), 16, 10);
    $time_hi_and_version &= 0x0FFF;
    $time_hi_and_version |= (4 << 12);

    $clock_seq_hi_and_reserved = base_convert(substr($hex, 16, 4), 16, 10);
    $clock_seq_hi_and_reserved &= 0x3F;
    $clock_seq_hi_and_reserved |= 0x80;

    $clock_seq_low = substr($hex, 20, 2);
    $nodes = substr($hex, 20);

    $uuid = sprintf('%s-%s-%04x-%02x%02x-%s',
      $time_low, $time_mid,
      $time_hi_and_version, $clock_seq_hi_and_reserved,
      $clock_seq_low, $nodes);

    return $uuid;
  }

}
