<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\FieldDefinitionInterface.
 */

namespace Drupal\Core\Entity\Field;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for entity field definitions.
 *
 * An entity field is a data object that holds the values of a particular field
 * for a particular entity (see \Drupal\Core\Entity\Field\FieldItemListInterface). For
 * example, $node_1->body and $node_2->body contain different data and therefore
 * are different field objects.
 *
 * In contrast, an entity field *definition* is an object that returns
 * information *about* a field (e.g., its type and settings) rather than its
 * values. As such, if all the information about $node_1->body and $node_2->body
 * is the same, then the same field definition object can be used to describe
 * both.
 *
 * It is up to the class implementing this interface to manage where the
 * information comes from. For example, field.module provides an implementation
 * based on two levels of configuration. It allows the site administrator to add
 * custom fields to any entity type and bundle via the "field_entity" and
 * "field_instance" configuration entities. The former for storing configuration
 * that is independent of which entity type and bundle the field is added to,
 * and the latter for storing configuration that is specific to the entity type
 * and bundle. The class that implements "field_instance" configuration entities
 * also implements this interface, returning information from either itself, or
 * from the corresponding "field_entity" configuration, as appropriate.
 *
 * However, entity base fields, such as $node->title, are not managed by
 * field.module and its "field_entity"/"field_instance" configuration entities.
 * Therefore, their definitions are provided by different objects that implement
 * this interface.
 * @todo That is still in progress: https://drupal.org/node/1949932. Update this
 *   documentation with details when that's implemented.
 *
 * Field definitions may fully define a concrete data object (e.g.,
 * $node_1->body), or may provide a best-guess definition for a data object that
 * might come into existence later. For example, $node_1->body and $node_2->body
 * may have different definitions (e.g., if the node types are different). When
 * adding the "body" field to a View that can return nodes of different types,
 * the View can get a field definition that represents the "body" field
 * abstractly, and present Views configuration options to the administrator
 * based on that abstract definition, even though that abstract definition can
 * differ from the concrete definition of any particular node's body field.
 */
interface FieldDefinitionInterface {

  /**
   * Returns the machine name of the field.
   *
   * This defines how the field data is accessed from the entity. For example,
   * if the field name is "foo", then $entity->foo returns its data.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName();

  /**
   * Returns the field type.
   *
   * @return string
   *   The field type, i.e. the id of a field type plugin. For example 'text'.
   *
   * @see \Drupal\Core\Entity\Field\FieldTypePluginManager
   */
  public function getFieldType();

  /**
   * Returns the field settings.
   *
   * Each field type defines the settings that are meaningful for that type.
   * For example, a text field can define a 'max_length' setting, and an image
   * field can define a 'alt_field_required' setting.
   *
   * @return array
   *   An array of key/value pairs.
   */
  public function getFieldSettings();

  /**
   * Returns the value of a given field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getFieldSetting($setting_name);

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
   * @return array
   *   The property names.
   */
  public function getFieldPropertyNames();

  /**
   * Returns whether the field is translatable.
   *
   * @return bool
   *   TRUE if the field is translatable.
   */
  public function isFieldTranslatable();

  /**
   * Determines whether the field is configurable via field.module.
   *
   * @return bool
   */
  public function isFieldConfigurable();

  /**
   * Returns the human-readable label for the field.
   *
   * @return string
   *   The field label.
   */
  public function getFieldLabel();

  /**
   * Returns the human-readable description for the field.
   *
   * This is displayed in addition to the label in places where additional
   * descriptive information is helpful. For example, as help text below the
   * form element in entity edit forms.
   *
   * @return string
   *   The field description.
   */
  public function getFieldDescription();

  /**
   * Returns the maximum number of items allowed for the field.
   *
   * Possible values are positive integers or FIELD_CARDINALITY_UNLIMITED.
   *
   * @return integer
   *   The field cardinality.
   */
  public function getFieldCardinality();

  /**
   * Returns whether at least one non-empty item is required for this field.
   *
   * Currently, required-ness is only enforced at the Form API level in entity
   * edit forms, not during direct API saves.
   *
   * @return bool
   *   TRUE if the field is required.
   */
  public function isFieldRequired();

  /**
   * Returns the default value for the field in a newly created entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being created.
   *
   * @return mixed
   *   The default value for the field, as accepted by
   *   Drupal\field\Plugin\Core\Entity\Field::setValue(). This can be either:
   *   - a literal, in which case it will be assigned to the first property of
   *     the first item.
   *   - a numerically indexed array of items, each item being a property/value
   *     array.
   *   - NULL or array() for no default value.
   */
  public function getFieldDefaultValue(EntityInterface $entity);

}
