<?php

/**
 * @file
 * Contains \Drupal\Core\Field\PluginSettingsInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Interface definition for plugin with settings.
 */
interface PluginSettingsInterface extends PluginInspectionInterface, ThirdPartySettingsInterface {

  /**
   * Defines the default settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings();

  /**
   * Returns the array of settings, including defaults for missing settings.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings();

  /**
   * Returns the value of a setting, or its default value if absent.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($key);

  /**
   * Sets the settings for the plugin.
   *
   * @param array $settings
   *   The array of settings, keyed by setting names. Missing settings will be
   *   assigned their default values.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Sets the value of a setting for the plugin.
   *
   * @param string $key
   *   The setting name.
   * @param mixed $value
   *   The setting value.
   *
   * @return $this
   */
  public function setSetting($key, $value);

  /**
   * Informs the plugin that some configuration it depends on will be deleted.
   *
   * This method allows plugins to keep their configuration up-to-date when a
   * dependency calculated with ::calculateDependencies() is removed. For
   * example, an entity view display contains a formatter having a setting
   * pointing to an arbitrary config entity. When that config entity is deleted,
   * this method is called by the view display to react to the dependency
   * removal by updating its configuration.
   *
   * This method must return TRUE if the removal event updated the plugin
   * configuration or FALSE otherwise.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are 'config', 'content', 'module' and 'theme'.
   *
   * @return bool
   *   TRUE if the plugin configuration has changed, FALSE if not.
   *
   * @see \Drupal\Core\Entity\EntityDisplayBase
   */
  public function onDependencyRemoval(array $dependencies);

}
