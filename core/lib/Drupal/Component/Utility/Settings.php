<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Settings.
 */

namespace Drupal\Component\Utility;

/**
 * Read only settings that are initialized with the class.
 *
 * @ingroup utility
 */
final class Settings {

  /**
   * Array with the settings.
   *
   * @var array
   */
  private $storage = array();

  /**
   * Singleton instance.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  private static $instance;

  /**
   * Returns the settings instance.
   *
   * A singleton is used because this class is used before the container is
   * available.
   *
   * @return \Drupal\Component\Utility\Settings
   */
  public static function getInstance() {
    return self::$instance;
  }

  /**
   * Returns a setting.
   *
   * Settings can be set in settings.php in the $settings array and requested
   * by this function. Settings should be used over configuration for read-only,
   * possibly low bootstrap configuration that is environment specific.
   *
   * @param string $name
   *   The name of the setting to return.
   * @param mixed $default
   *   (optional) The default value to use if this setting is not set.
   *
   * @return mixed
   *   The value of the setting, the provided default if not set.
   */
  public static function get($name, $default = NULL) {
    return self::$instance->getSetting($name, $default);
  }

  /**
   * Returns all the settings. This is only used for testing purposes.
   *
   * @return array
   *   All the settings.
   */
  public static function getAll() {
    return self::$instance->getAllSettings();
  }

  /**
   * Constructor.
   *
   * @param array $settings
   *   Array with the settings.
   */
  function __construct(array $settings) {
    $this->storage = $settings;
    self::$instance = $this;
  }

  /**
   * Returns a setting.
   *
   * Settings can be set in settings.php in the $settings array and requested
   * by this function. Settings should be used over configuration for read-only,
   * possibly low bootstrap configuration that is environment specific.
   *
   * @param string $name
   *   The name of the setting to return.
   * @param mixed $default
   *   (optional) The default value to use if this setting is not set.
   *
   * @return mixed
   *   The value of the setting, the provided default if not set.
   */
  public function getSetting($name, $default = NULL) {
    return isset($this->storage[$name]) ? $this->storage[$name] : $default;
  }

  /**
   * Returns all the settings. This is only used for testing purposes.
   *
   * @return array
   *   All the settings.
   */
  public function getAllSettings() {
    return $this->storage;
  }

}
