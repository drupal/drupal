<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class ContentEntityNormalizer extends EntityNormalizer {

  /**
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $entity
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return array
   *   The normalized data.
   */
  public function normalize($entity, $format = NULL, array $context = []): array {
    $context += [
      'account' => NULL,
    ];

    $attributes = [];
    /** @var \Drupal\Core\Entity\Entity $entity */
    foreach (TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData()) as $name => $field_items) {
      if ($field_items->access('view', $context['account'])) {
        $attributes[$name] = $this->serializer->normalize($field_items, $format, $context);
      }
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ContentEntityInterface::class => TRUE,
    ];
  }

}
