<?php

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
 *
 * @ingroup typed_data
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
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If an unsupported data type gets passed to the class; e.g., 'string' to a
   *   definition class handling 'entity:* data types.
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
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The label. A string or an instance of TranslatableMarkup will be returned
   *   based on the way the label translation is handled. NULL if no label is
   *   available.
   */
  public function getLabel();

  /**
   * Returns a human readable description.
   *
   * Descriptions are usually used on user interfaces where the data is edited
   * or displayed.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The description. A string or an instance of TranslatableMarkup will be
   *   returned based on the way the description translation is handled. NULL if
   *   no description is available.
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
   * If not specified, the default class of the data type will be returned.
   *
   * @return string
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
   *   The setting value or NULL if the setting name doesn't exist.
   */
  public function getSetting($setting_name);

  /**
   * Returns an array of validation constraints.
   *
   * The validation constraints of a definition consist of any for it defined
   * constraints and default constraints, which are generated based on the
   * definition and its data type. See
   * \Drupal\Core\TypedData\TypedDataManager::getDefaultConstraints().
   *
   * Constraints are defined via an array, having constraint plugin IDs as key
   * and constraint options as values, e.g.
   * @code
   * $constraints = array(
   *   'Range' => array('min' => 5, 'max' => 10),
   *   'NotBlank' => array(),
   * );
   * @endcode
   * Options have to be specified using another array if the constraint has more
   * than one or zero options. If it has exactly one option, the value should be
   * specified without nesting it into another array:
   * @code
   * $constraints = array(
   *   'EntityType' => 'node',
   *   'Bundle' => 'article',
   * );
   * @endcode
   *
   * Note that the specified constraints must be compatible with the data type,
   * e.g. for data of type 'entity' the 'EntityType' and 'Bundle' constraints
   * may be specified.
   *
   * @see \Drupal\Core\Validation\ConstraintManager
   *
   * @return array[]
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   *
   * @see \Symfony\Component\Validator\Constraint
   */
  public function getConstraints();

  /**
   * Returns a validation constraint.
   *
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details.
   *
   * @param string $constraint_name
   *   The name of the constraint, i.e. its plugin id.
   *
   * @return array
   *   A validation constraint definition which can be used for instantiating a
   *   \Symfony\Component\Validator\Constraint object.
   *
   * @see \Symfony\Component\Validator\Constraint
   */
  public function getConstraint($constraint_name);

  /**
   * Adds a validation constraint.
   *
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details.
   *
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array|null $options
   *   The constraint options as required by the constraint plugin, or NULL.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function addConstraint($constraint_name, $options = NULL);

  /**
   * Determines whether the data value is internal.
   *
   * This can be used in a scenario when it is not desirable to expose this data
   * value to an external system.
   *
   * The implications of this method are left to the discretion of the caller.
   * For example, a module providing an HTTP API may not expose entities of
   * this type, or a custom entity reference field settings form may
   * deprioritize entities of this type in a select list.
   *
   * @return bool
   *   Whether the data value is internal.
   */
  public function isInternal();

}
