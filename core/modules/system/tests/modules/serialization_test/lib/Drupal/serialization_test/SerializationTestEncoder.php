<?php

/**
 * @file
 * Definition of Drupal\serialization_test\SerializationTestEncoder.
 */

namespace Drupal\serialization_test;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

class SerializationTestEncoder implements EncoderInterface {

  /**
   * The format that this Encoder supports.
   *
   * @var string
   */
  static protected $format = 'serialization_test';

  /**
   * Encodes data into the requested format.
   *
   * @param mixed $data
   *   Data to encode.
   * @param string $format
   *   Format name.
   *
   * @return string
   *   A string representation of $data in the requested format.
   */
  public function encode($data, $format) {
    // @see Drupal\serialization_test\SerializationTestNormalizer::normalize().
    return 'Normalized by ' . $data['normalized_by'] . ', Encoded by SerializationTestEncoder';
  }

  /**
   * Checks whether this encoder can encode to the requested format.
   *
   * @param string $format
   *   The short name of the format.
   *
   * @return bool
   *   Returns TRUE if this encoder can encode to the requested format.
   */
  public function supportsEncoding($format) {
    return static::$format === $format;
  }
}
