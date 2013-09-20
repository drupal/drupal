<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TestDataConverter.
 */

namespace Drupal\rdf\Tests\Field;

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
  static function convertFoo($data) {
    return 'foo' . $data['value'];
  }

}
