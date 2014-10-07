<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ThirdPartySettingsTrait.
 */

namespace Drupal\Core\Config\Entity;

/**
 * Provides generic implementation of ThirdPartySettingsInterface.
 *
 * The name of the property used to store third party settings is
 * 'third_party_settings'. You need to provide configuration schema for that
 * setting to ensure it is persisted. See 'third_party_settings' defined on
 * field_config_base and other 'field_config.third_party.*' types.
 *
 * @see \Drupal\Core\Config\Entity\ThirdPartySettingsInterface
 */
trait ThirdPartySettingsTrait {

  /**
   * Third party entity settings.
   *
   * An array of key/value pairs keyed by provider.
   *
   * @var array
   */
  protected $third_party_settings = array();

  /**
   * Sets the value of a third-party setting.
   *
   * @param string $module
   *   The module providing the third-party setting.
   * @param string $key
   *   The setting name.
   * @param mixed $value
   *   The setting value.
   *
   * @return $this
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->third_party_settings[$module][$key] = $value;
    return $this;
  }

  /**
   * Gets the value of a third-party setting.
   *
   * @param string $module
   *   The module providing the third-party setting.
   * @param string $key
   *   The setting name.
   * @param mixed $default
   *   The default value
   *
   * @return mixed
   *   The value.
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    if (isset($this->third_party_settings[$module][$key])) {
      return $this->third_party_settings[$module][$key];
    }
    else {
      return $default;
    }
  }

  /**
   * Gets all third-party settings of a given module.
   *
   * @param string $module
   *   The module providing the third-party settings.
   *
   * @return array
   *   An array of key-value pairs.
   */
  public function getThirdPartySettings($module) {
    return isset($this->third_party_settings[$module]) ? $this->third_party_settings[$module] : array();
  }

  /**
   * Unsets a third-party setting.
   *
   * @param string $module
   *   The module providing the third-party setting.
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The value.
   */
  public function unsetThirdPartySetting($module, $key) {
    unset($this->third_party_settings[$module][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this module.
    if (empty($this->third_party_settings[$module])) {
      unset($this->third_party_settings[$module]);
    }
    return $this;
  }

  /**
   * Gets the list of third parties that store information.
   *
   * @return array
   *   The list of third parties.
   */
  public function getThirdPartyProviders() {
    return array_keys($this->third_party_settings);
  }

}
