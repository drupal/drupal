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
  protected $supportedInterfaceOrClass = Data::class;

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
    return TRUE;
  }

}
