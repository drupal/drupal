<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldStorageDefinitionInterface.
 */

namespace Drupal\Core\Field;

/**
 * Defines an interface for entity field storage definitions.
 *
 * Field storage definitions represent the part of full field definitions (see
 * FieldDefinitionInterface) that is responsible for defining how the field is
 * stored. While field definitions may differ by entity bundle, all of those
 * bundle fields have to share the same common field storage definition. Thus,
 * the storage definitions can be defined by entity type only.
 * The bundle fields corresponding to a field storage definition may provide
 * additional information; e.g., they may provide bundle-specific settings or
 * constraints that are not present in the storage definition. However bundle
 * fields may not override or alter any information provided by the storage
 * definition except for the label and the description; e.g., any constraints
 * and settings on the storage definition must be present on the bundle field as
 * well.
 *
 * @see hook_entity_field_storage_info()
 */
interface FieldStorageDefinitionInterface {

  /**
   * Value indicating a field accepts an unlimited number of values.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * Returns the machine name of the field.
   *
   * This defines how the field data is accessed from the entity. For example,
   * if the field name is "foo", then $entity->foo returns its data.
   *
   * @return string
   *   The field name.
   */
  public function getName();

  /**
   * Returns the field type.
   *
   * @return string
   *   The field type, i.e. the id of a field type plugin. For example 'text'.
   *
   * @see \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  public function getType();

  /**
   * Returns the field settings.
   *
   * Each field type defines the settings that are meaningful for that type.
   * For example, a text field can define a 'max_length' setting, and an image
   * field can define a 'alt_field_required' setting.
   *
   * @return mixed[]
   *   An array of key/value pairs.
   */
  public function getSettings();

  /**
   * Returns the value of a given field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Returns whether the field is translatable.
   *
   * @return bool
   *   TRUE if the field is translatable.
   */
  public function isTranslatable();

  /**
   * Returns whether the field is revisionable.
   *
   * @return bool
   *   TRUE if the field is revisionable.
   */
  public function isRevisionable();

  /**
   * Determines whether the field is queryable via QueryInterface.
   *
   * @return bool
   *   TRUE if the field is queryable.
   */
  public function isQueryable();

  /**
   * Returns the human-readable label for the field.
   *
   * @return string
   *   The field label.
   */
  public function getLabel();

  /**
   * Returns the human-readable description for the field.
   *
   * This is displayed in addition to the label in places where additional
   * descriptive information is helpful. For example, as help text below the
   * form element in entity edit forms.
   *
   * @return string|null
   *   The field description, or NULL if no description is available.
   */
  public function getDescription();

  /**
   * Returns whether the field can contain multiple items.
   *
   * @return bool
   *   TRUE if the field can contain multiple items, FALSE otherwise.
   */
  public function isMultiple();

  /**
   * Returns the maximum number of items allowed for the field.
   *
   * Possible values are positive integers or
   * FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED.
   *
   * @return int
   *   The field cardinality.
   */
  public function getCardinality();

  /**
   * Gets the definition of a contained property.
   *
   * @param string $name
   *   The name of property.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|null
   *   The definition of the property or NULL if the property does not exist.
   */
  public function getPropertyDefinition($name);

  /**
   * Gets an array of property definitions of contained properties.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   */
  public function getPropertyDefinitions();

  /**
   * Returns the names of the field's subproperties.
   *
   * A field is a list of items, and each item can contain one or more
   * properties. All items for a given field contain the same property names,
   * but the values can be different for each item.
   *
   * For example, an email field might just contain a single 'value' property,
   * while a link field might contain 'title' and 'url' properties, and a text
   * field might contain 'value', 'summary', and 'format' properties.
   *
   * @return string[]
   *   The property names.
   */
  public function getPropertyNames();

  /**
   * Returns the name of the main property, if any.
   *
   * Some field items consist mainly of one main property, e.g. the value of a
   * text field or the @code target_id @endcode of an entity reference. If the
   * field item has no main property, the method returns NULL.
   *
   * @return string|null
   *   The name of the value property, or NULL if there is none.
   */
  public function getMainPropertyName();

  /**
   * Returns the ID of the type of the entity this field is attached to.
   *
   * This method should not be confused with EntityInterface::entityType()
   * (configurable fields are config entities, and thus implement both
   * interfaces):
   *   - FieldDefinitionInterface::getTargetEntityTypeId() answers "as a field,
   *     which entity type are you attached to?".
   *   - EntityInterface::getEntityTypeId() answers "as a (config) entity, what
   *     is your own entity type".
   *
   * @return string
   *   The name of the entity type.
   */
  public function getTargetEntityTypeId();

  /**
   * Returns the field schema.
   *
   * Note that this method returns an empty array for computed fields which have
   * no schema.
   *
   * @return array[]
   *   The field schema, as an array of key/value pairs in the format returned
   *   by hook_field_schema():
   *   - columns: An array of Schema API column specifications, keyed by column
   *     name. This specifies what comprises a single value for a given field.
   *     No assumptions should be made on how storage backends internally use
   *     the original column name to structure their storage.
   *   - indexes: An array of Schema API index definitions. Some storage
   *     backends might not support indexes.
   *   - foreign keys: An array of Schema API foreign key definitions. Note,
   *     however, that depending on the storage backend specified for the field,
   *     the field data is not necessarily stored in SQL.
   */
  public function getSchema();

  /**
   * Returns the field columns, as defined in the field schema.
   *
   * @return array[]
   *   The array of field columns, keyed by column name, in the same format
   *   returned by getSchema().
   *
   * @see \Drupal\Core\Field\FieldDefinitionInterface::getSchema()
   */
  public function getColumns();

  /**
   * Returns an array of validation constraints.
   *
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
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
   * See \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @param string $constraint_name
   *   The name of the the constraint, i.e. its plugin id.
   *
   * @return array
   *   A validation constraint definition which can be used for instantiating a
   *   \Symfony\Component\Validator\Constraint object.
   *
   * @see \Symfony\Component\Validator\Constraint
   */
  public function getConstraint($constraint_name);

  /**
   * Returns the name of the provider of this field.
   *
   * @return string
   *   The provider name; e.g., the module name.
   */
  public function getProvider();

  /**
   * Returns the storage behavior for this field.
   *
   * Indicates whether the entity type's storage should take care of storing the
   * field values or whether it is handled separately; e.g. by the
   * module providing the field.
   *
   * @return bool
   *   FALSE if the storage takes care of storing the field, TRUE otherwise.
   */
  public function hasCustomStorage();

}
