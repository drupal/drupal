<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\MarkupNormalizer.
 */

namespace Drupal\serialization\Normalizer;

/**
 * Normalizes MarkupInterface objects into a string.
 */
class MarkupNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = array('Drupal\Component\Render\MarkupInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    return (string) $object;
  }

}
