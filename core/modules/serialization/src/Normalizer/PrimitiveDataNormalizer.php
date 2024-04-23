<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Converts primitive data objects to their casted values.
 */
class PrimitiveDataNormalizer extends NormalizerBase {

  use SerializedColumnNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    // Add cacheability if applicable.
    $this->addCacheableDependency($context, $object);

    $parent = $object->getParent();
    if ($parent instanceof FieldItemInterface && $object->getValue()) {
      $serialized_property_names = $this->getCustomSerializedPropertyNames($parent);
      if (in_array($object->getName(), $serialized_property_names, TRUE)) {
        return unserialize($object->getValue());
      }
    }

    // Typed data casts NULL objects to their empty variants, so for example
    // the empty string ('') for string type data, or 0 for integer typed data.
    // In a better world with typed data implementing algebraic data types,
    // getCastedValue would return NULL, but as typed data is not aware of real
    // optional values on the primitive level, we implement our own optional
    // value normalization here.
    return $object->getValue() === NULL ? NULL : $object->getCastedValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      PrimitiveInterface::class => TRUE,
    ];
  }

}
