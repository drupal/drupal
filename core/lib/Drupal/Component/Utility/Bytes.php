<?php

namespace Drupal\Component\Utility;

/**
 * Provides helper methods for byte conversions.
 */
class Bytes {

  /**
   * The number of bytes in a kilobyte.
   *
   * @see http://wikipedia.org/wiki/Kilobyte
   */
  const KILOBYTE = 1024;

  /**
   * Parses a given byte size.
   *
   * @param mixed $size
   *   An integer or string size expressed as a number of bytes with optional SI
   *   or IEC binary unit prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8 bytes, 9mbytes).
   *
   * @return int
   *   An integer representation of the size in bytes.
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Utility\Bytes::toNumber() instead
   *
   * @see https://www.drupal.org/node/3162663
   */
  public static function toInt($size) {
    @trigger_error('\Drupal\Component\Utility\Bytes::toInt() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Utility\Bytes::toNumber() instead. See https://www.drupal.org/node/3162663', E_USER_DEPRECATED);
    return self::toNumber($size);
  }

  /**
   * Parses a given byte size.
   *
   * @param int|float|string $size
   *   An integer, float, or string size expressed as a number of bytes with
   *   optional SI or IEC binary unit prefix (e.g. 2, 2.4, 3K, 5MB, 10G, 6GiB,
   *   8 bytes, 9mbytes).
   *
   * @return float
   *   The floating point value of the size in bytes.
   */
  public static function toNumber($size): float {
    // Remove the non-unit characters from the size.
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    // Remove the non-numeric characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
      // Find the position of the unit in the ordered string which is the power
      // of magnitude to multiply a kilobyte by.
      return round($size * pow(self::KILOBYTE, stripos('bkmgtpezy', $unit[0])));
    }
    else {
      // Ensure size is a proper number type.
      return round((float) $size);
    }
  }

}
