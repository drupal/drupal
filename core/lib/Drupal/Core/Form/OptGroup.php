<?php

namespace Drupal\Core\Form;

/**
 * Provides helpers for HTML option groups.
 */
class OptGroup {

  /**
   * Allows PHP array processing of multiple select options with the same value.
   *
   * Used for form select elements which need to validate HTML option groups
   * and multiple options which may return the same value. Associative PHP
   * arrays cannot handle these structures, since they share a common key.
   *
   * @param array $array
   *   The form options array to process.
   *
   * @return array
   *   An array with all hierarchical elements flattened to a single array.
   */
  public static function flattenOptions(array $array) {
    $options = array();
    static::doFlattenOptions($array, $options);
    return $options;
  }

  /**
   * Iterates over an array building a flat array with duplicate keys removed.
   *
   * This function also handles cases where objects are passed as array values.
   *
   * @param array $array
   *   The form options array to process.
   * @param array $options
   *   The array of flattened options.
   */
  protected static function doFlattenOptions(array $array, array &$options) {
    foreach ($array as $key => $value) {
      if (is_object($value) && isset($value->option)) {
        static::doFlattenOptions($value->option, $options);
      }
      elseif (is_array($value)) {
        static::doFlattenOptions($value, $options);
      }
      else {
        $options[$key] = $value;
      }
    }
  }

}
