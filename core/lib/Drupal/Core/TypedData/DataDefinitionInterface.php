<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\DataDefinitionInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for data definitions.
 *
 * Data definitions are used to describe data based upon available data types.
 * For example, a plugin could describe its parameters using data definitions
 * in order to specify what kind of data is required for it.
 *
 * @see \Drupal\Core\TypedData\DataDefinition
 */
interface DataDefinitionInterface {

  /**
   * Returns the data type of the data.
   *
   * @return string
   *   The data type.
   */
  public function getDataType();

  /**
   * Returns a human readable label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Returns a human readable description.
   *
   * @return string|null
   *   The description, or NULL if no description is available.
   */
  public function getDescription();

  /**
   * Returns whether the data is multi-valued, i.e. a list of data items.
   *
   * This is equivalent to checking whether the data definition implements the
   * \Drupal\Core\TypedData\ListDefinitionInterface interface.
   *
   * @return bool
   *   Whether the data is multi-valued.
   */
  public function isList();

  /**
   * Determines whether the data is read-only.
   *
   * @return bool
   *   Whether the data is read-only.
   */
  public function isReadOnly();

  /**
   * Determines whether the data value is computed.
   *
   * For example, data could be computed depending on some other values.
   *
   * @return bool
   *   Whether the data value is computed.
   */
  public function isComputed();

  /**
   * Determines whether a data value is required.
   *
   * For required data a non-NULL value is mandatory.
   *
   * @return bool
   *   Whether a data value is required.
   */
  public function isRequired();

  /**
   * Returns the class used for creating the typed data object.
   *
   * If not specified, the default class of the data type will be used.
   *
   * @return string|null
   *   The class used for creating the typed data object.
   */
  public function getClass();

  /**
   * Returns the array of settings, as required by the used class.
   *
   * See the documentation of the class for supported or required settings.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings();

  /**
   * Returns an array of validation constraints.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @return array
   *   Array of constraints, each being an instance of
   *   \Symfony\Component\Validator\Constraint.
   */
  public function getConstraints();

}
