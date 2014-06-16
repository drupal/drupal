<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataInterface.
 */

namespace Drupal\Core\TypedData;

use Drupal\user;

/**
 * Interface for typed data objects.
 *
 * @see \Drupal\Core\TypedData\DataDefinitionInterface
 *
 * @ingroup typed_data
 */
interface TypedDataInterface {

  /**
   * Gets the data definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition object.
   */
  public function getDataDefinition();

  /**
   * Gets the data value.
   *
   * @return mixed
   */
  public function getValue();

  /**
   * Sets the data value.
   *
   * @param mixed|null $value
   *   The value to set in the format as documented for the data type or NULL to
   *   unset the data value.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it to FALSE to
   *   avoid being notified again.
   *
   * @throws \Drupal\Core\TypedData\ReadOnlyException
   *   If the data is read-only.
   */
  public function setValue($value, $notify = TRUE);

  /**
   * Returns a string representation of the data.
   *
   * @return string
   */
  public function getString();

  /**
   * Gets a list of validation constraints.
   *
   * @return array
   *   Array of constraints, each being an instance of
   *   \Symfony\Component\Validator\Constraint.
   */
  public function getConstraints();

  /**
   * Validates the currently set data value.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate();

  /**
   * Applies the default value.
   *
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it to FALSE to
   *   avoid being notified again.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Returns itself to allow for chaining.
   */
  public function applyDefaultValue($notify = TRUE);

  /**
   * Returns the name of a property or item.
   *
   * @return string
   *   If the data is a property of some complex data, the name of the property.
   *   If the data is an item of a list, the name is the numeric position of the
   *   item in the list, starting with 0. Otherwise, NULL is returned.
   */
  public function getName();

  /**
   * Returns the parent data structure; i.e. either complex data or a list.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface|\Drupal\Core\TypedData\ListInterface
   *   The parent data structure, either complex data or a list; or NULL if this
   *   is the root of the typed data tree.
   */
  public function getParent();

  /**
   * Returns the root of the typed data tree.
   *
   * Returns the root data for a tree of typed data objects; e.g. for an entity
   * field item the root of the tree is its parent entity object.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface|\Drupal\Core\TypedData\ListInterface
   *   The root data structure, either complex data or a list.
   */
  public function getRoot();

  /**
   * Returns the property path of the data.
   *
   * The trail of property names relative to the root of the typed data tree,
   * separated by dots; e.g. 'field_text.0.format'.
   *
   * @return string
   *   The property path relative to the root of the typed tree, or an empty
   *   string if this is the root.
   */
  public function getPropertyPath();

  /**
   * Sets the context of a property or item via a context aware parent.
   *
   * This method is supposed to be called by the factory only.
   *
   * @param string $name
   *   (optional) The name of the property or the delta of the list item,
   *   or NULL if it is the root of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   */
  public function setContext($name = NULL, TypedDataInterface $parent = NULL);
}
