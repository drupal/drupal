<?php

declare(strict_types=1);

namespace Drupal\serialization_test;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Serialization encoder used for testing.
 */
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
  public function encode($data, $format, array $context = []): string {
    // @see \Drupal\serialization_test\SerializationTestNormalizer::normalize().
    return 'Normalized by ' . $data['normalized_by'] . ', Encoded by SerializationTestEncoder';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding(string $format, array $context = []): bool {
    return static::$format === $format;
  }

}
