<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;

/**
 * Normalizes JSON:API Data objects.
 *
 * @internal
 */
class DataNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof Data);
    $cacheable_normalizations = array_map(function ($resource) use ($format, $context) {
      return $this->serializer->normalize($resource, $format, $context);
    }, $object->toArray());
    return $object->getCardinality() === 1
      ? array_shift($cacheable_normalizations) ?: CacheableNormalization::permanent(NULL)
      : CacheableNormalization::aggregate($cacheable_normalizations);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use getSupportedTypes() instead. See https://www.drupal.org/node/3359695', E_USER_DEPRECATED);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      Data::class => TRUE,
    ];
  }

}
