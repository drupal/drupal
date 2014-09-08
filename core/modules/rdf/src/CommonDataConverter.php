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
  public static function rawValue($data) {
    return $data;
  }

  /**
   * Converts a date entity field array into an ISO 8601 timestamp string.
   *
   * @param array $data
   *   The array containing the 'value' element.
   *
   * @return string
   *   Returns the ISO 8601 timestamp.
   */
  public static function dateIso8601Value($data) {
    return date_iso8601($data['value']);
  }

}
