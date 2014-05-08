<?php

/**
 * @file
 * Contains \Drupal\Core\Site\Settings.
 */

namespace Drupal\Core\Site;

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
   * @var \Drupal\Core\Site\Settings
   */
  private static $instance;

  /**
   * Constructor.
   *
   * @param array $settings
   *   Array with the settings.
   */
  public function __construct(array $settings) {
    $this->storage = $settings;
    self::$instance = $this;
  }

  /**
   * Returns the settings instance.
   *
   * A singleton is used because this class is used before the container is
   * available.
   *
   * @return \Drupal\Core\Site\Settings
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
    return isset(self::$instance->storage[$name]) ? self::$instance->storage[$name] : $default;
  }

  /**
   * Returns all the settings. This is only used for testing purposes.
   *
   * @return array
   *   All the settings.
   */
  public static function getAll() {
    return self::$instance->storage;
  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   */
  public static function getHashSalt() {
    $hash_salt = self::$instance->get('hash_salt');
    // This should never happen, as it breaks user logins and many other
    // services. Therefore, explicitly notify the user (developer) by throwing
    // an exception.
    if (empty($hash_salt)) {
      throw new \RuntimeException('Missing $settings[\'hash_salt\'] in settings.php.');
    }

    return $hash_salt;
  }

}
