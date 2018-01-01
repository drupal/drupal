<?php

namespace Drupal\serialization\Normalizer;

/**
 * Converts typed data objects to arrays.
 */
class TypedDataNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\TypedDataInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $this->addCacheableDependency($context, $object);
    $value = $object->getValue();
    // Support for stringable value objects: avoid numerous custom normalizers.
    if (is_object($value) && method_exists($value, '__toString')) {
      $value = (string) $value;
    }
    return $value;
  }

}
