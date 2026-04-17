<?php

declare(strict_types=1);

namespace Drupal\serialization_test;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serialization normalizer used for testing.
 */
class SerializationTestNormalizer implements NormalizerInterface {

  /**
   * The format that this Normalizer supports.
   *
   * @var string
   */
  protected static $format = 'serialization_test';

  /**
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return array
   *   The normalized data.
   */
  public function normalize($object, $format = NULL, array $context = []): array {
    $normalized = (array) $object;
    // Add identifying value that can be used to verify that the expected
    // normalizer was invoked.
    $normalized['normalized_by'] = 'SerializationTestNormalizer';
    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, ?string $format = NULL, array $context = []): bool {
    return static::$format === $format;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      \stdClass::class => TRUE,
    ];
  }

}
