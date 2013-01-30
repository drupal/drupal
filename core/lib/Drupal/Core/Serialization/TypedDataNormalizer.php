<?php

/**
 * @file
 * Contains \Drupal\Core\Serialization\TypedDataNormalizer.
 */

namespace Drupal\Core\Serialization;

use Drupal\Core\Serialization\NormalizerBase;
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
  protected static $supportedInterfaceOrClass = 'Drupal\Core\TypedData\TypedDataInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize().
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    return $object->getValue();
  }

}
