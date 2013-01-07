<?php

/**
 * @file
 * Contains Drupal\Component\Utility\Settings.
 */

namespace Drupal\Component\Utility;

class Settings {

  /**
   * @var array
   */
  protected $storage;

  /**
   * @var Settings
   */
  static $singleton;

  /**
   * @param array $settings
   */
  function __construct(array $settings) {
    $this->storage = $settings;
    self::$singleton = $this;
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
  public function get($name, $default = NULL) {
    return isset($this->storage[$name]) ? $this->storage[$name] : $default;
  }

  /**
   * Returns all the settings. This is only used for testing purposes.
   *
   * @return array
   *   All the settings.
   */
  public function getAll() {
    return $this->storage;
  }

}
