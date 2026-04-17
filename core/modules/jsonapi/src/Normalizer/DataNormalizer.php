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
   * Normalizes data into a set of arrays/scalars.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Data $object
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
  public function getSupportedTypes(?string $format): array {
    return [
      Data::class => TRUE,
    ];
  }

}
