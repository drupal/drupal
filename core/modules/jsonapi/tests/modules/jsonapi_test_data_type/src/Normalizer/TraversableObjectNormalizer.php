<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_data_type\Normalizer;

use Drupal\jsonapi_test_data_type\TraversableObject;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizes TraversableObject.
 */
class TraversableObjectNormalizer extends NormalizerBase {

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
   * @return array|string|int|float|bool|\ArrayObject<mixed, mixed>|null
   *   \ArrayObject is used to make sure an empty object is encoded as an
   *   object not an array.
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return $object->property;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [TraversableObject::class => TRUE];
  }

}
