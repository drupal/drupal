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
   * {@inheritdoc}
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
