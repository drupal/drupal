<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Normalizes/denormalizes Drupal config entity objects into an array structure.
 */
class ConfigEntityNormalizer extends EntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ConfigEntityInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return static::getDataWithoutInternals($object->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    return parent::denormalize(static::getDataWithoutInternals($data), $class, $format, $context);
  }

  /**
   * Gets the given data without the internal implementation details.
   *
   * @param array $data
   *   The data that is either currently or about to be stored in configuration.
   *
   * @return array
   *   The same data, but without internals. Currently, that is only the '_core'
   *   key, which is reserved by Drupal core to handle complex edge cases
   *   correctly. Data in the '_core' key is irrelevant to clients reading
   *   configuration, and is not allowed to be set by clients writing
   *   configuration: it is for Drupal core only, and managed by Drupal core.
   *
   * @see https://www.drupal.org/node/2653358
   */
  protected static function getDataWithoutInternals(array $data) {
    return array_diff_key($data, ['_core' => TRUE]);
  }

}
