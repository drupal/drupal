<?php

namespace Drupal\serialization\Normalizer;

/**
 * Null normalizer.
 */
class NullNormalizer extends NormalizerBase {

  /**
   * Constructs a NullNormalizer object.
   *
   * @param string|array $supported_interface_of_class
   *   The supported interface(s) or class(es).
   */
  public function __construct($supported_interface_of_class) {
    $this->supportedInterfaceOrClass = $supported_interface_of_class;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return NULL;
  }

}
