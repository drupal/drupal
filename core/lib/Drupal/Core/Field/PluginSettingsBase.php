<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for the Field API plugins.
 *
 * This class handles lazy replacement of default settings values.
 */
abstract class PluginSettingsBase extends PluginBase implements PluginSettingsInterface, DependentPluginInterface {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The plugin settings injected by third party modules.
   *
   * @see hooks
   *
   * @var array
   */
  protected $thirdPartySettings = [];

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultSettingsMerged = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Merge defaults before returning the array.
    if (!$this->defaultSettingsMerged) {
      $this->mergeDefaults();
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
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
    $this->settings += static::defaultSettings();
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    $this->defaultSettingsMerged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module = NULL) {
    if ($module) {
      return isset($this->thirdPartySettings[$module]) ? $this->thirdPartySettings[$module] : [];
    }
    return $this->thirdPartySettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    return isset($this->thirdPartySettings[$module][$key]) ? $this->thirdPartySettings[$module][$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->thirdPartySettings[$module][$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    unset($this->thirdPartySettings[$module][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this module.
    if (empty($this->thirdPartySettings[$module])) {
      unset($this->thirdPartySettings[$module]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return array_keys($this->thirdPartySettings);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (!empty($this->thirdPartySettings)) {
      // Create dependencies on any modules providing third party settings.
      return [
        'module' => array_keys($this->thirdPartySettings)
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = FALSE;
    if (!empty($this->thirdPartySettings) && !empty($dependencies['module'])) {
      $old_count = count($this->thirdPartySettings);
      $this->thirdPartySettings = array_diff_key($this->thirdPartySettings, array_flip($dependencies['module']));
      $changed = $old_count != count($this->thirdPartySettings);
    }
    return $changed;
  }

}
