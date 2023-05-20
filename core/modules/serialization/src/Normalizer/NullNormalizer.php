<?php

namespace Drupal\serialization\Normalizer;

/**
 * Null normalizer.
 */
class NullNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string[]
   */
  protected array $supportedTypes = ['*' => FALSE];

  /**
   * Constructs a NullNormalizer object.
   *
   * @param string|array $supported_interface_of_class
   *   The supported interface(s) or class(es).
   */
  public function __construct($supported_interface_of_class) {
    $this->supportedTypes = [$supported_interface_of_class => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return $this->supportedTypes;
  }

}
