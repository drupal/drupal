<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\SafeStringNormalizer.
 */

namespace Drupal\serialization\Normalizer;

/**
 * Normalizes SafeStringInterface objects into a string.
 */
class SafeStringNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = array('Drupal\Component\Utility\SafeStringInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    return (string) $object;
  }

}
