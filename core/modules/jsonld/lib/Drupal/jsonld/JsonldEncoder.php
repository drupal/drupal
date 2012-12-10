<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEncoder.
 */

namespace Drupal\jsonld;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Encodes JSON-LD data.
 *
 * Simply respond to JSON-LD requests using the JSON encoder.
 */
class JsonldEncoder extends JsonEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  static protected $format = array('jsonld', 'drupal_jsonld');

  /**
   * Overrides \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsEncoding()
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * Overrides \Symfony\Component\Serializer\Encoder\JsonEncoder::supportsDecoding()
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

}
