<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines an interface for the field type plugin manager.
 *
 * @ingroup field_types
 */
interface FieldTypePluginManagerInterface extends PluginManagerInterface, CategorizingPluginManagerInterface {

  /**
   * Creates a new field item list.
   *
   * The provided entity is assigned as the parent of the created item list.
   * However, it is the responsibility of the caller (usually the parent entity
   * itself) to make the parent aware of the field as a new child.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity this field item list will be part of.
   * @param string $field_name
   *   The name of the field.
   * @param mixed $values
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The instantiated object.
   */
  public function createFieldItemList(FieldableEntityInterface $entity, $field_name, $values = NULL);

  /**
   * Creates a new field item as part of a field item list.
   *
   * The provided item list is assigned as the parent of the created item. It
   * However, it is the responsibility of the caller (usually the parent list
   * itself) to have the parent aware of the item as a new child.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list, for which to create a new item.
   * @param int $index
   *   The list index at which the item is created.
   * @param array|null $values
   *   (optional) The values to assign to the field item properties.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The instantiated object.
   */
  public function createFieldItem(FieldItemListInterface $items, $index, $values = NULL);

  /**
   * Returns the default field-level settings for a field type.
   *
   * @param string $type
   *   A field type name.
   *
   * @return array
   *   The field's default settings, as provided by the plugin definition, or
   *   an empty array if type or settings are undefined.
   */
  public function getDefaultFieldSettings($type);

  /**
   * Returns the default storage-level settings for a field type.
   *
   * @param string $type
   *   A field type name.
   *
   * @return array
   *   The type's default settings, as provided by the plugin definition, or an
   *   empty array if type or settings are undefined.
   */
  public function getDefaultStorageSettings($type);

  /**
   * Returns the summary of storage-level settings for a field type.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return array
   *   A renderable array for the field's storage-level settings summary, as
   *   provided by the plugin definition.
   */
  public function getStorageSettingsSummary(FieldStorageDefinitionInterface $storage_definition): array;

  /**
   * Returns the summary of field-level settings for a field type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field entity.
   *
   * @return array
   *   A renderable array for the field's field-level settings summary, as
   *   provided by the plugin definition.
   */
  public function getFieldSettingsSummary(FieldDefinitionInterface $field_definition): array;

  /**
   * Gets the definition of all field types that can be added via UI.
   *
   * If the field type extends
   * \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface, then include the
   * preconfigured definitions. The key is 'field_ui', the base field name, and
   * the key from getPreconfiguredOptions(), joined with ':'.
   *
   * @return array
   *   An array of field type definitions.
   */
  public function getUiDefinitions();

  /**
   * Returns preconfigured field options for a field type.
   *
   * This is a wrapper around
   * \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions()
   * allowing modules to alter the result of this method by implementing
   * hook_field_ui_preconfigured_options_alter().
   *
   * @param string $field_type
   *   The field type plugin ID.
   *
   * @return array
   *   A multi-dimensional array as returned from
   *   \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions().
   *
   * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions()
   * @see hook_field_ui_preconfigured_options_alter()
   */
  public function getPreconfiguredOptions($field_type);

  /**
   * Returns the PHP class that implements the field type plugin.
   *
   * @param string $type
   *   A field type name.
   *
   * @return string
   *   Field type plugin class name.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the field type plugin name is invalid.
   */
  public function getPluginClass($type);

}
