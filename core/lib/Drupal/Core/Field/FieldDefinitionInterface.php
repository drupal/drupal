<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldDefinitionInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * Defines an interface for entity field definitions.
 *
 * An entity field is a data object that holds the values of a particular field
 * for a particular entity (see \Drupal\Core\Field\FieldItemListInterface). For
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
 * custom fields to any entity type and bundle via the "field_storage_config"
 * and "field_config" configuration entities. The former for storing
 * configuration that is independent of which entity type and bundle the field
 * is added to, and the latter for storing configuration that is specific to the
 * entity type and bundle. The class that implements "field_config"
 * configuration entities also implements this interface, returning information
 * from either itself, or from the corresponding "field_storage_config"
 * configuration, as appropriate.
 *
 * However, entity base fields, such as $node->title, are not managed by
 * field.module and its "field_storage_config"/"field_config"
 * configuration entities. Therefore, their definitions are provided by
 * different objects based on the class \Drupal\Core\Field\BaseFieldDefinition,
 * which implements this interface as well.
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
interface FieldDefinitionInterface extends ListDataDefinitionInterface {

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
   * Returns the ID of the entity type the field is attached to.
   *
   * This method should not be confused with EntityInterface::getEntityTypeId()
   * (configurable fields are config entities, and thus implement both
   * interfaces):
   *   - FieldDefinitionInterface::getTargetEntityTypeId() answers "as a field,
   *     which entity type are you attached to?".
   *   - EntityInterface::getEntityTypeId() answers "as a (config) entity, what
   *     is your own entity type?".
   *
   * @return string
   *   The entity type ID.
   */
  public function getTargetEntityTypeId();

  /**
   * Gets the bundle the field is attached to.
   *
   * This method should not be confused with EntityInterface::bundle()
   * (configurable fields are config entities, and thus implement both
   * interfaces):
   *   - FieldDefinitionInterface::getTargetBundle() answers "as a field,
   *     which bundle are you attached to?".
   *   - EntityInterface::bundle() answers "as a (config) entity, what
   *     is your own bundle?" (not relevant in our case, the config entity types
   *     used to store the definitions of configurable fields do not have
   *     bundles).
   *
   * @return string|null
   *   The bundle the field is defined for, or NULL if it is a base field; i.e.,
   *   it is not bundle-specific.
   */
  public function getTargetBundle();

  /**
   * Returns whether the display for the field can be configured.
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   *
   * @return bool
   *   TRUE if the display for this field is configurable in the given context.
   *   If TRUE, the display options returned by getDisplayOptions() may be
   *   overridden via the respective entity display.
   *
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  public function isDisplayConfigurable($display_context);

  /**
   * Returns the default display options for the field.
   *
   * If the field's display is configurable, the returned display options act
   * as default values and may be overridden via the respective entity display.
   * Otherwise, the display options will be applied to entity displays as is.
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   *
   * @return array|null
   *   The array of display options for the field, or NULL if the field is not
   *   displayed. The following key/value pairs may be present:
   *   - label: (string) Position of the field label. The default 'field' theme
   *     implementation supports the values 'inline', 'above' and 'hidden'.
   *     Defaults to 'above'. Only applies to 'view' context.
   *   - type: (string) The plugin (widget or formatter depending on
   *     $display_context) to use, or 'hidden'. If not specified or if the
   *     requested plugin is unknown, the 'default_widget' / 'default_formatter'
   *     for the field type will be used.
   *   - settings: (array) Settings for the plugin specified above. The default
   *     settings for the plugin will be used for settings left unspecified.
   *   - third_party_settings: (array) Settings provided by other extensions
   *     through hook_field_formatter_third_party_settings_form().
   *   - weight: (float) The weight of the element. Not needed if 'type' is
   *     'hidden'.
   *   The defaults of the various display options above get applied by the used
   *   entity display.
   *
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  public function getDisplayOptions($display_context);

  /**
   * Returns whether at least one non-empty item is required for this field.
   *
   * Currently, required-ness is only enforced at the Form API level in entity
   * edit forms, not during direct API saves.
   *
   * @return bool
   *   TRUE if the field is required.
   */
  public function isRequired();

  /**
   * Returns the default value for the field in a newly created entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the default value is generated.
   *
   * @return mixed
   *   The default value for the field, as accepted by
   *   \Drupal\field\Plugin\Core\Entity\FieldItemListInterface::setValue(). This
   *   can be either:
   *   - a literal, in which case it will be assigned to the first property of
   *     the first item.
   *   - a numerically indexed array of items, each item being a property/value
   *     array.
   *   - NULL or array() for no default value.
   */
  public function getDefaultValue(ContentEntityInterface $entity);

  /**
   * Returns whether the field is translatable.
   *
   * @return bool
   *   TRUE if the field is translatable.
   */
  public function isTranslatable();

  /**
   * Returns the field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition.
   */
  public function getFieldStorageDefinition();

  /**
   * Gets an object that can be saved in configuration.
   *
   * Base fields are defined in code. In order to configure field definition
   * properties per bundle use this method to create an override that can be
   * saved in configuration.
   *
   * @see \Drupal\Core\Field\Entity\BaseFieldBundleOverride
   *
   * @param string $bundle
   *   The bundle to get the configurable field for.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   */
  public function getConfig($bundle);

}
