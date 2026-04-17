<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\ListInterface;

/**
 * Converts list objects to arrays.
 *
 * Ordinarily, this would be handled automatically by Serializer, but since
 * there is a TypedDataNormalizer and the Field class extends TypedData, any
 * Field will be handled by that Normalizer instead of being traversed. This
 * class ensures that TypedData classes that also implement ListInterface are
 * traversed instead of simply returning getValue().
 */
class ListNormalizer extends NormalizerBase {

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
    $attributes = [];
    foreach ($object as $fieldItem) {
      $attributes[] = $this->serializer->normalize($fieldItem, $format, $context);
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ListInterface::class => TRUE,
    ];
  }

}
