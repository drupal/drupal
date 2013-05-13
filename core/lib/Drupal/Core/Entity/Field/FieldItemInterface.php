<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\FieldItemInterface.
 */

namespace Drupal\Core\Entity\Field;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Interface for entity field items.
 *
 * Entity field items are typed data objects containing the field values, i.e.
 * implementing the ComplexDataInterface.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Entity\Field\FieldInterface
 * @see \Drupal\Core\Entity\Field\FieldItemBase
 */
interface FieldItemInterface extends ComplexDataInterface {

  /**
   * Magic method: Gets a property value.
   *
   * @param $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @throws \InvalidArgumentException
   *   If a not existing property is accessed.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The property object.
   */
  public function __get($property_name);

  /**
   * Magic method: Sets a property value.
   *
   * @param $property_name
   *   The name of the property to set; e.g., 'title' or 'name'.
   * @param $value
   *   The value to set, or NULL to unset the property. Optionally, a typed
   *   data object implementing Drupal\Core\TypedData\TypedDataInterface may be
   *   passed instead of a plain value.
   *
   * @throws \InvalidArgumentException
   *   If a not existing property is set.
   */
  public function __set($property_name, $value);

  /**
   * Magic method: Determines whether a property is set.
   *
   * @param $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return boolean
   *   Returns TRUE if the property exists and is set, FALSE otherwise.
   */
  public function __isset($property_name);

  /**
   * Magic method: Unsets a property.
   *
   * @param $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   */
  public function __unset($property_name);
}
