<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;

/**
 * Normalizes a JSON:API relationship object.
 *
 * @internal
 */
class RelationshipNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = Relationship::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($object instanceof Relationship);
    return CacheableNormalization::aggregate([
      'data' => $this->serializer->normalize($object->getData(), $format, $context),
      'links' => $this->serializer->normalize($object->getLinks(), $format, $context)->omitIfEmpty(),
      'meta' => CacheableNormalization::permanent($object->getMeta())->omitIfEmpty(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    return TRUE;
  }

}
