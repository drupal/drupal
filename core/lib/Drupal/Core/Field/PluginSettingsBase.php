<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\PluginSettingsBase.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for the Field API plugins.
 *
 * This class handles lazy replacement of default settings values.
 */
abstract class PluginSettingsBase extends PluginBase implements PluginSettingsInterface {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultSettingsMerged = FALSE;

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getSettings().
   */
  public function getSettings() {
    // Merge defaults before returning the array.
    if (!$this->defaultSettingsMerged) {
      $this->mergeDefaults();
    }
    return $this->settings;
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getSetting().
   */
  public function getSetting($key) {
    // Merge defaults if we have no value for the key.
    if (!$this->defaultSettingsMerged && !array_key_exists($key, $this->settings)) {
      $this->mergeDefaults();
    }
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * Merges default settings values into $settings.
   */
  protected function mergeDefaults() {
    $this->settings += $this->getDefaultSettings();
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getDefaultSettings().
   */
  public function getDefaultSettings() {
    $definition = $this->getPluginDefinition();
    return $definition['settings'];
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::setSettings().
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    $this->defaultSettingsMerged = FALSE;
    return $this;
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::setSetting().
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

}
