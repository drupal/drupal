<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\TypedDataNormalizer.
 */

namespace Drupal\serialization\Normalizer;

use Symfony\Component\Serializer\Exception\RuntimeException;

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
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize().
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    return $object->getValue();
  }

}
