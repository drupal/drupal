<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEncoder.
 */

namespace Drupal\jsonld;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Encodes JSON-LD data.
 *
 * Simply respond to JSON-LD requests using the JSON encoder.
 */
class JsonldEncoder extends JsonEncoder implements EncoderInterface {

  /**
   * The format that this Encoder supports.
   *
   * @var string
   */
  static protected $format = 'jsonld';

  /**
   * Check whether the request is for JSON-LD.
   *
   * @param string $format
   *   The short name of the format returned by ContentNegotiation.
   *
   * @return bool
   *   Returns TRUE if the encoder can handle the request.
   */
  public function supportsEncoding($format) {
    return static::$format === $format;
  }
}
