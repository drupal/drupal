<?php

namespace Drupal\serialization_test;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

class SerializationTestEncoder implements EncoderInterface {

  /**
   * The format that this Encoder supports.
   *
   * @var string
   */
  protected static $format = 'serialization_test';

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    // @see \Drupal\serialization_test\SerializationTestNormalizer::normalize().
    return 'Normalized by ' . $data['normalized_by'] . ', Encoded by SerializationTestEncoder';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return static::$format === $format;
  }

}
