<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\MetadataFactory.
 */

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\MetadataFactoryInterface;

/**
 * Typed data implementation of the validator MetadataFactoryInterface.
 */
class MetadataFactory implements MetadataFactoryInterface {

  /**
   * Implements MetadataFactoryInterface::getMetadataFor().
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   Some typed data object containing the value to validate.
   * @param $name
   *   (optional) The name of the property to get metadata for. Leave empty, if
   *   the data is the root of the typed data tree.
   */
  public function getMetadataFor($typed_data, $name = '') {
    if (!$typed_data instanceof TypedDataInterface) {
      throw new \InvalidArgumentException('The passed value must be a typed data object.');
    }
    $is_container = $typed_data instanceof ComplexDataInterface || $typed_data instanceof ListInterface;
    $class = '\Drupal\Core\TypedData\Validation\\' . ($is_container ? 'PropertyContainerMetadata' : 'Metadata');
    return new $class($typed_data, $name, $this);
  }

  /**
   * Implements MetadataFactoryInterface::hasMetadataFor().
   */
  public function hasMetadataFor($value) {
    return $value instanceof TypedDataInterface;
  }
}
