<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Serialization\Attribute\JsonSchema;

/**
 * Null normalizer.
 */
class NullNormalizer extends NormalizerBase {

  use SchematicNormalizerTrait;

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
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return null
   *   The normalized data.
   */
  #[JsonSchema(['type' => 'null'])]
  public function doNormalize($object, $format = NULL, array $context = []): NULL {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    return ['type' => 'null'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return $this->supportedTypes;
  }

}
