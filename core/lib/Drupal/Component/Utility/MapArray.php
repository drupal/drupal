<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\MapArray.
 */

namespace Drupal\Component\Utility;

/**
 * Provides generic array mapping helper methods.
 */
class MapArray {

  /**
   * Forms an associative array from a linear array.
   *
   * This function walks through the provided array and constructs an associative
   * array out of it. The keys of the resulting array will be the values of the
   * input array. The values will be the same as the keys unless a function is
   * specified, in which case the output of the function is used for the values
   * instead.
   *
   * @param array $array
   *   A linear array.
   * @param callable $callback
   *   A name of a function to apply to all values before output.
   *
   * @return array
   *   An associative array.
   */
  public static function copyValuesToKeys(array $array, $callable = NULL) {
    // array_combine() fails with empty arrays:
    // http://bugs.php.net/bug.php?id=34857.
    if (!empty($array)) {
      $array = array_combine($array, $array);
    }
    if (is_callable($callable)) {
      $array = array_map($callable, $array);
    }

    return $array;
  }

}
