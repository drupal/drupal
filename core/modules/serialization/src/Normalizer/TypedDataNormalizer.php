<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Converts typed data objects to arrays.
 */
class TypedDataNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = TypedDataInterface::class;

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

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    return TRUE;
  }

}
