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
   */
  public static function toInt($size) {
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
      return round($size);
    }
  }

}
