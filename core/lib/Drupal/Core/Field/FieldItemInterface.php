<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldItemInterface.
 */

namespace Drupal\Core\Field;

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
 * @see \Drupal\Core\Field\FieldItemListInterface
 * @see \Drupal\Core\Field\FieldItemBase
 * @ingroup field_types
 */
interface FieldItemInterface extends ComplexDataInterface {

  /**
   * Defines field item properties.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   *
   * @see \Drupal\Core\Field\FieldDefinition
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition);

  /**
   * Returns the name of the main property, if any.
   *
   * Some field items consist mainly of one main property, e.g. the value of a
   * text field or the @code target_id @endcode of an entity reference. If the
   * field item has no main property, the method returns NULL.
   *
   * @return string|null
   *   The name of the value property, or NULL if there is none.
   *
   * @see \Drupal\Core\Field\FieldDefinition
   */
  public static function mainPropertyName();

  /**
   * Returns the schema for the field.
   *
   * This method is static because the field schema information is needed on
   * creation of the field. FieldItemInterface objects instantiated at that
   * time are not reliable as field instance settings might be missing.
   *
   * Computed fields having no schema should return an empty array.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An empty array if there is no schema, or an associative array with the
   *   following key/value pairs:
   *   - columns: An array of Schema API column specifications, keyed by column
   *     name. The columns need to be a subset of the properties defined in
   *     propertyDefinitions(). It is recommended to avoid having the column
   *     definitions depend on field settings when possible. No assumptions
   *     should be made on how storage engines internally use the original
   *     column name to structure their storage.
   *   - unique keys: (optional) An array of Schema API unique key definitions.
   *     Only columns that appear in the 'columns' array are allowed.
   *   - indexes: (optional) An array of Schema API index definitions. Only
   *     columns that appear in the 'columns' array are allowed. Those indexes
   *     will be used as default indexes. Callers of field_create_field() can
   *     specify additional indexes or, at their own risk, modify the default
   *     indexes specified by the field-type module. Some storage engines might
   *     not support indexes.
   *   - foreign keys: (optional) An array of Schema API foreign key
   *     definitions. Note, however, that the field data is not necessarily
   *     stored in SQL. Also, the possible usage is limited, as you cannot
   *     specify another field as related, only existing SQL tables,
   *     such as {taxonomy_term_data}.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition);

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
   * @return \Drupal\Core\Field\FieldDefinitionInterface
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
   * Returns a renderable array for a single field item.
   *
   * @param array $display_options
   *   Can be either the name of a view mode, or an array of display settings.
   *   See EntityViewBuilderInterface::viewField() for more information.
   *
   * @return array
   *   A renderable array for the field item.
   *
   * @see \Drupal\Core\Entity\EntityViewBuilderInterface::viewField()
   * @see \Drupal\Core\Entity\EntityViewBuilderInterface::viewFieldItem()
   * @see \Drupal\Core\Field\FieldItemListInterface::view()
   */
  public function view($display_options = array());

  /**
   * Defines custom presave behavior for field values.
   *
   * This method is called before insert() and update() methods, and before
   * values are written into storage.
   */
  public function preSave();

  /**
   * Defines custom insert behavior for field values.
   *
   * This method is called during the process of inserting an entity, just
   * before values are written into storage.
   */
  public function insert();

  /**
   * Defines custom update behavior for field values.
   *
   * This method is called during the process of updating an entity, just before
   * values are written into storage.
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

  /**
   * Defines the field-level settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings();

  /**
   * Defines the instance-level settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultInstanceSettings();

  /**
   * Returns a form for the field-level settings.
   *
   * Invoked from \Drupal\field_ui\Form\FieldEditForm to allow administrators to
   * configure field-level settings.
   *
   * Field storage might reject field definition changes that affect the field
   * storage schema if the field already has data. When the $has_data parameter
   * is TRUE, the form should not allow changing the settings that take part in
   * the schema() method. It is recommended to set #access to FALSE on the
   * corresponding elements.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   * @param bool $has_data
   *   TRUE if the field already has data, FALSE if not.
   *
   * @return
   *   The form definition for the field settings.
   */
  public function settingsForm(array &$form, array &$form_state, $has_data);

  /**
   * Returns a form for the instance-level settings.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure instance-level settings.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the field instance settings.
   */
  public function instanceSettingsForm(array $form, array &$form_state);

}
