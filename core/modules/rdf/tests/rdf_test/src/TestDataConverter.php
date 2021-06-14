<?php

namespace Drupal\rdf_test;

/**
 * Contains methods for test data conversions.
 */
class TestDataConverter {

  /**
   * Converts data into a string for placement into a content attribute.
   *
   * @param array $data
   *   The data to be altered and placed in the content attribute.
   *
   * @return string
   *   Returns the data.
   */
  public static function convertFoo($data) {
    return 'foo' . $data['value'];
  }

}
