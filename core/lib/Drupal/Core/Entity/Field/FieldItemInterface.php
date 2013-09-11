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
   * Gets the entity that field belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity();

  /**
   * Gets the langcode of the field values held in the object.
   *
   * @return $langcode
   *   The langcode.
   */
  public function getLangcode();

  /**
   * Gets the field definition.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition();

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

  /**
   * Defines custom presave behavior for field values.
   *
   * This method is called before either insert() or update() methods, and
   * before values are written into storage.
   */
  public function preSave();

  /**
   * Defines custom insert behavior for field values.
   *
   * This method is called after the save() method, and before values are
   * written into storage.
   */
  public function insert();

  /**
   * Defines custom update behavior for field values.
   *
   * This method is called after the save() method, and before values are
   * written into storage.
   */
  public function update();

  /**
   * Defines custom delete behavior for field values.
   *
   * This method is called during the process of deleting an entity, just before
   * values are deleted from storage.
   */
  public function delete();

  /**
   * Defines custom revision delete behavior for field values.
   *
   * This method is called from during the process of deleting an entity
   * revision, just before the field values are deleted from storage. It is only
   * called for entity types that support revisioning.
   */
  public function deleteRevision();

}
