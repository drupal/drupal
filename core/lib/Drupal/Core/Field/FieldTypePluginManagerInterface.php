<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldTypePluginManagerInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for the field type plugin manager.
 *
 * @ingroup field_types
 */
interface FieldTypePluginManagerInterface extends PluginManagerInterface {

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
   * Gets the definition of all field types that can be added via UI.
   *
   * @return array
   *   An array of field type definitions.
   */
  public function getUiDefinitions();

  /**
   * Returns the PHP class that implements the field type plugin.
   *
   * @param string $type
   *   A field type name.
   *
   * @return string
   *   Field type plugin class name.
   */
  public function getPluginClass($type);

}
