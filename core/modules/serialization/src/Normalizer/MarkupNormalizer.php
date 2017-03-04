<?php

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
  protected $supportedInterfaceOrClass = ['Drupal\Component\Render\MarkupInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return (string) $object;
  }

}
