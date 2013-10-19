<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\PluginSettingsInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface definition for plugin with settings.
 */
interface PluginSettingsInterface extends PluginInspectionInterface {

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
   * Returns the default settings for the plugin.
   *
   * @return array
   *   The array of default setting values, keyed by setting names.
   */
  public function getDefaultSettings();

  /**
   * Sets the settings for the plugin.
   *
   * @param array $settings
   *   The array of settings, keyed by setting names. Missing settings will be
   *   assigned their default values.
   *
   * @return \Drupal\field\Plugin\PluginSettingsInterface
   *   The plugin itself.
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
   * @return \Drupal\field\Plugin\PluginSettingsInterface
   *   The plugin itself.
   */
  public function setSetting($key, $value);

}
