<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Converts primitive data objects to their casted values.
 */
class PrimitiveDataNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = PrimitiveInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // Typed data casts NULL objects to their empty variants, so for example
    // the empty string ('') for string type data, or 0 for integer typed data.
    // In a better world with typed data implementing algebraic data types,
    // getCastedValue would return NULL, but as typed data is not aware of real
    // optional values on the primitive level, we implement our own optional
    // value normalization here.
    return $object->getValue() === NULL ? NULL : $object->getCastedValue();
  }

}
