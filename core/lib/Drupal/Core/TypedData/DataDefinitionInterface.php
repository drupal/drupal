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
 * Definitions that describe lists or complex data have to implement the
 * respective interfaces, such that the metadata about contained list items or
 * properties can be retrieved from the definition.
 *
 * @see \Drupal\Core\TypedData\DataDefinition
 * @see \Drupal\Core\TypedData\ListDefinitionInterface
 * @see \Drupal\Core\TypedData\ComplexDataDefinitionInterface
 * @see \Drupal\Core\TypedData\DataReferenceDefinitionInterface
 * @see \Drupal\Core\TypedData\TypedDataInterface
 */
interface DataDefinitionInterface {

  /**
   * Creates a new data definition object.
   *
   * This method is typically used by
   * \Drupal\Core\TypedData\TypedDataManager::createDataDefinition() to build a
   * definition object for an arbitrary data type. When the definition class is
   * known, it is recommended to directly use the static create() method on that
   * class instead; e.g.:
   * @code
   *   $map_definition = \Drupal\Core\TypedData\MapDataDefinition::create();
   * @endcode
   *
   * @param string $data_type
   *   The data type, for which a data definition should be created.
   *
   * @throws \InvalidArgumentException
   *   If an unsupported data type gets passed to the class; e.g., 'string' to a
   *   definition class handling 'entity:* data types.
   *
   * @return static
   */
   public static function createFromDataType($data_type);

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
   * Returns the value of a given setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Returns an array of validation constraints.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @return array
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getConstraints();

  /**
   * Returns a validation constraint.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @param string $constraint_name
   *   The name of the the constraint, i.e. its plugin id.
   *
   * @return array
   *   A validation constraint definition which can be used for instantiating a
   *   \Symfony\Component\Validator\Constraint object.
   */
  public function getConstraint($constraint_name);

}
