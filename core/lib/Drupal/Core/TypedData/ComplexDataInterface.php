<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ComplexDataInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for complex data; i.e. data containing named and typed properties.
 *
 * The name of a property has to be a valid PHP variable name, starting with
 * an alphabetic character.
 *
 * This is implemented by entities as well as by field item classes of
 * entities.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\TypedData\ComplexDataDefinitionInterface
 *
 * @ingroup typed_data
 */
interface ComplexDataInterface extends TraversableTypedDataInterface  {

  /**
   * Gets a property object.
   *
   * @param $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The property object.
   *
   * @throws \InvalidArgumentException
   *   If an invalid property name is given.
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no property can be created.
   */
  public function get($property_name);

  /**
   * Sets a property value.
   *
   * @param $property_name
   *   The name of the property to set; e.g., 'title' or 'name'.
   * @param $value
   *   The value to set, or NULL to unset the property.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If the update stems from a parent object, set it to FALSE to avoid
   *   being notified again.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The property object.
   *
   * @throws \InvalidArgumentException
   *   If the specified property does not exist.
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no property can be set.
   */
  public function set($property_name, $value, $notify = TRUE);

  /**
   * Gets an array of property objects.
   *
   * @param bool $include_computed
   *   If set to TRUE, computed properties are included. Defaults to FALSE.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface[]
   *   An array of property objects implementing the TypedDataInterface, keyed
   *   by property name.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no property can be created.
   */
  public function getProperties($include_computed = FALSE);

  /**
   * Returns an array of all property values.
   *
   * Gets an array of plain property values including all not-computed
   * properties.
   *
   * @return array
   *   An array of property values, keyed by property name.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   If the complex data structure is unset and no property can be created.
   */
  public function toArray();

  /**
   * Determines whether the data structure is empty.
   *
   * @return boolean
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public function isEmpty();

}
