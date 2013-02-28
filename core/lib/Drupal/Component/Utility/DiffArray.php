<?php

/**
 * @file
 * Contains Drupal\Component\Utility\DiffArray.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helpers to perform diffs on multi dimensional arrays.
 */
class DiffArray {

  /**
   * Computes the difference between arrays.
   *
   * This is just a wrapper around array_diff_assoc(). The only reason Drupal
   * provides this function is in since PHP 5.4 casting of arrays to strings
   * throws a notice. If you want to use array_diff_assoc() as it was up to PHP
   * 5.3, then use this function but consider the consequences -- it should be
   * rare to use this function.
   *
   * @param array $array1
   *   The array to compare from.
   * @param array $array2
   *   The array to compare to.
   *
   * @return array
   *   Returns an array containing all the values from array1 that are not present
   *   in array2.
   */
  public static function diffAssoc(array $array1, array $array2) {
    return @array_diff_assoc($array1, $array2);
  }

  /**
   * Recursively computes the difference of arrays with additional index check.
   *
   * This is a version of array_diff_assoc() that supports multidimensional
   * arrays.
   *
   * @param array $array1
   *   The array to compare from.
   * @param array $array2
   *   The array to compare to.
   *
   * @return array
   *   Returns an array containing all the values from array1 that are not present
   *   in array2.
   */
  public static function diffAssocRecursive(array $array1, array $array2) {
    $difference = array();

    foreach ($array1 as $key => $value) {
      if (is_array($value)) {
        if (!array_key_exists($key, $array2) && !is_array($array2[$key])) {
          $difference[$key] = $value;
        }
        else {
          $new_diff = static::diffAssocRecursive($value, $array2[$key]);
          if (!empty($new_diff)) {
            $difference[$key] = $new_diff;
          }
        }
      }
      elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
        $difference[$key] = $value;
      }
    }

    return $difference;
  }

}
