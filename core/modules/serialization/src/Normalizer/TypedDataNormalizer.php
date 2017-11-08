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
    return $object->getValue();
  }

}
