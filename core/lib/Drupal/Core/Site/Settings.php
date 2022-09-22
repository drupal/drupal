<?php

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
  private $storage = [];

  /**
   * Singleton instance.
   *
   * @var \Drupal\Core\Site\Settings
   */
  private static $instance = NULL;

  /**
   * Information about all deprecated settings, keyed by legacy settings name.
   *
   * Each entry should be an array that defines the following keys:
   *   - 'replacement': The new name for the setting.
   *   - 'message': The deprecation message to use for trigger_error().
   *
   * @var array
   *
   * @see self::handleDeprecations()
   */
  private static $deprecatedSettings = [
    'sanitize_input_whitelist' => [
      'replacement' => 'sanitize_input_safe_keys',
      'message' => 'The "sanitize_input_whitelist" setting is deprecated in drupal:9.1.0 and will be removed in drupal:10.0.0. Use Drupal\Core\Security\RequestSanitizer::SANITIZE_INPUT_SAFE_KEYS instead. See https://www.drupal.org/node/3163148.',
    ],
    'twig_sandbox_whitelisted_classes' => [
      'replacement' => 'twig_sandbox_allowed_classes',
      'message' => 'The "twig_sandbox_whitelisted_classes" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_classes" instead. See https://www.drupal.org/node/3162897.',
    ],
    'twig_sandbox_whitelisted_methods' => [
      'replacement' => 'twig_sandbox_allowed_methods',
      'message' => 'The "twig_sandbox_whitelisted_methods" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_methods" instead. See https://www.drupal.org/node/3162897.',
    ],
    'twig_sandbox_whitelisted_prefixes' => [
      'replacement' => 'twig_sandbox_allowed_prefixes',
      'message' => 'The "twig_sandbox_whitelisted_prefixes" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_prefixes" instead. See https://www.drupal.org/node/3162897.',
    ],
  ];

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
   *
   * @throws \BadMethodCallException
   *   Thrown when the settings instance has not been initialized yet.
   */
  public static function getInstance() {
    if (self::$instance === NULL) {
      throw new \BadMethodCallException('Settings::$instance is not initialized yet. Whatever you are trying to do, it might be too early for that. You could call Settings::initialize(), but it is probably better to wait until it is called in the regular way. Also check for recursions.');
    }
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
    // If the caller is asking for the value of a deprecated setting, trigger a
    // deprecation message about it.
    if (isset(self::$deprecatedSettings[$name])) {
      @trigger_error(self::$deprecatedSettings[$name]['message'], E_USER_DEPRECATED);
    }
    return self::$instance->storage[$name] ?? $default;
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
   *   decorated.
   *
   * @see default.settings.php
   */
  public static function initialize($app_root, $site_path, &$class_loader) {
    // Export these settings.php variables to the global namespace.
    global $config;
    $settings = [];
    $config = [];
    $databases = [];

    if (is_readable($app_root . '/' . $site_path . '/settings.php')) {
      require $app_root . '/' . $site_path . '/settings.php';
    }

    self::handleDeprecations($settings);

    // Initialize databases.
    Database::setMultipleConnectionInfo($databases, $class_loader, $app_root);

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
   * Generates a prefix for APCu user cache keys.
   *
   * A standardized prefix is useful to allow visual inspection of an APCu user
   * cache. By default, this method will produce a unique prefix per site using
   * the hash salt. If the setting 'apcu_ensure_unique_prefix' is set to FALSE
   * then if the caller does not provide a $site_path only the Drupal root will
   * be used. This allows tests to use the same prefix ensuring that the number
   * of APCu items created during a full test run is kept to a minimum.
   * Additionally, if a multi site implementation does not use site specific
   * module directories setting apcu_ensure_unique_prefix would allow the sites
   * to share APCu cache items.
   *
   * @param string $identifier
   *   An identifier for the prefix. For example, 'class_loader' or
   *   'cache_backend'.
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   (optional) The site path. Defaults to an empty string.
   *
   * @return string
   *   The prefix for APCu user cache keys.
   *
   * @see https://www.drupal.org/project/drupal/issues/2926309
   */
  public static function getApcuPrefix($identifier, $root, $site_path = '') {
    if (static::get('apcu_ensure_unique_prefix', TRUE)) {
      return 'drupal.' . $identifier . '.' . \Drupal::VERSION . '.' . static::get('deployment_identifier') . '.' . hash_hmac('sha256', $identifier, static::get('hash_salt') . '.' . $root . '/' . $site_path);
    }
    return 'drupal.' . $identifier . '.' . \Drupal::VERSION . '.' . static::get('deployment_identifier') . '.' . Crypt::hashBase64($root . '/' . $site_path);
  }

  /**
   * Handle deprecated values in the site settings.
   *
   * @param array $settings
   *   The site settings.
   *
   * @see self::getDeprecatedSettings()
   */
  private static function handleDeprecations(array &$settings): void {
    foreach (self::$deprecatedSettings as $legacy => $deprecation) {
      if (!empty($settings[$legacy])) {
        @trigger_error($deprecation['message'], E_USER_DEPRECATED);
        // Set the new key if needed.
        if (!isset($settings[$deprecation['replacement']])) {
          $settings[$deprecation['replacement']] = $settings[$legacy];
        }
      }
      // Ensure that both keys have the same value.
      if (isset($settings[$deprecation['replacement']])) {
        $settings[$legacy] = $settings[$deprecation['replacement']];
      }
    }
  }

}
