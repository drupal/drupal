<?php

/**
 * @file
 * Contains \Drupal\Component\Serialization\Json.
 */

namespace Drupal\Component\Serialization;

/**
 * Default serialization for JSON.
 *
 * @ingroup third_party
 */
class Json implements SerializationInterface {

  /**
   * {@inheritdoc}
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public static function encode($variable) {
    // Encode <, >, ', &, and ".
    return json_encode($variable, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($string) {
    return json_decode($string, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'json';
  }

}
