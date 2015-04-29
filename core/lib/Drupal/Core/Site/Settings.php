<?php

/**
 * @file
 * Contains \Drupal\Core\Site\Settings.
 */

namespace Drupal\Core\Site;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;

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
   * Protects creating with clone.
   */
  private function __clone() {
  }

  /**
   * Prevents settings from being serialized.
   */
  public function __sleep() {
    throw new \LogicException('Settings can not be serialized. This probably means you are serializing an object that has an indirect reference to the Settings object. Adjust your code so that is not necessary.');
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
   * Bootstraps settings.php and the Settings singleton.
   *
   * @param string $app_root
   *   The app root.
   * @param string $site_path
   *   The current site path.
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The class loader that is used for this request. Passed by reference and
   *   exposed to the local scope of settings.php, so as to allow it to be
   *   decorated with Symfony's ApcClassLoader, for example.
   *
   * @see default.settings.php
   */
  public static function initialize($app_root, $site_path, &$class_loader) {
    // Export these settings.php variables to the global namespace.
    global $base_url, $config_directories, $config;
    $settings = array();
    $config = array();
    $databases = array();

    // Make conf_path() available as local variable in settings.php.
    if (is_readable($app_root . '/' . $site_path . '/settings.php')) {
      require $app_root . '/' . $site_path . '/settings.php';
    }

    // Initialize Database.
    Database::setMultipleConnectionInfo($databases);

    // Initialize Settings.
    new Settings($settings);
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

  /**
   * Generates a prefix for APC user cache keys.
   *
   * A standardized prefix is useful to allow visual inspection of an APC user
   * cache. By default, this method will produce a unique prefix per site using
   * the hash salt. If the setting 'apcu_ensure_unique_prefix' is set to FALSE
   * then if the caller does not provide a $site_path only the Drupal root will
   * be used. This allows WebTestBase to use the same prefix ensuring that the
   * number of APC items created during a full test run is kept to a minimum.
   * Additionally, if a multi site implementation does not use site specific
   * module directories setting apcu_ensure_unique_prefix would allow the sites
   * to share APC cache items.
   *
   * @param $identifier
   *   An identifier for the prefix. For example, 'class_loader' or
   *   'cache_backend'.
   *
   * @return string
   *   The prefix for APC user cache keys.
   */
  public static function getApcuPrefix($identifier, $root, $site_path = '') {
    if (static::get('apcu_ensure_unique_prefix', TRUE)) {
      return 'drupal.' . $identifier . '.' . hash_hmac('sha256', $identifier, static::get('hash_salt', $root . '/' . $site_path));
    }
    return 'drupal.' . $identifier . '.' . Crypt::hashBase64($root . '/' . $site_path);
  }

}
