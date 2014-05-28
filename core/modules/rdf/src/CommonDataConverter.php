<?php
/**
 * @file
 * Contains \Drupal\rdf\CommonDataConverter.
 */

namespace Drupal\rdf;

/**
 * Contains methods for common data conversions.
 */
class CommonDataConverter {

  /**
   * Provides a passthrough to place unformatted values in content attributes.
   *
   * @param mixed $data
   *   The data to be placed in the content attribute.
   *
   * @return mixed
   *   Returns the data.
   */
  static function rawValue($data) {
    return $data;
  }
}
