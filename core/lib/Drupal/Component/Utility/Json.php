<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Json.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helpers for dealing with json.
 */
class Json {

  /**
   * Converts a PHP variable into its JavaScript equivalent.
   *
   * We use HTML-safe strings, with several characters escaped.
   *
   * @param mixed $variable
   *   The variable to encode.
   *
   * @return string
   *   Returns the encoded variable.
   *
   * @see drupal_json_decode()
   * @ingroup php_wrappers
   */
  public static function encode($variable) {
    // Encode <, >, ', &, and " using the json_encode() options parameter.
    return json_encode($variable, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
  }

  /**
   * Converts an HTML-safe JSON string into its PHP equivalent.
   *
   * @param string $string
   *   The string to decode.
   *
   * @return mixed
   *   Returns the decoded string.
   *
   * @ingroup php_wrappers
   */
  public static function decode($string) {
    return json_decode($string, TRUE);
  }

}
