<?php

/**
 * @file
 * Contains \Drupal\Core\Config\BootstrapConfigStorageFactory.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;

/**
 * Defines a factory for retrieving the config storage used pre-kernel.
 */
class BootstrapConfigStorageFactory {

  /**
   * Returns a configuration storage implementation.
   *
   * @param $class_loader
   *   The class loader. Normally Composer's ClassLoader, as included by the
   *   front controller, but may also be decorated; e.g.,
   *   \Symfony\Component\ClassLoader\ApcClassLoader.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A configuration storage implementation.
   */
  public static function get($class_loader = NULL) {
    $bootstrap_config_storage = Settings::get('bootstrap_config_storage');
    $storage_backend = FALSE;
    if (!empty($bootstrap_config_storage) && is_callable($bootstrap_config_storage)) {
      $storage_backend = call_user_func($bootstrap_config_storage, $class_loader);
    }
    // Fallback to the DatabaseStorage.
    return $storage_backend ?: self::getDatabaseStorage();
  }

  /**
   * Returns a Database configuration storage implementation.
   *
   * @return \Drupal\Core\Config\DatabaseStorage
   */
  public static function getDatabaseStorage() {
    return new DatabaseStorage(Database::getConnection(), 'config');
  }

  /**
   * Returns a File-based configuration storage implementation.
   *
   * @return \Drupal\Core\Config\FileStorage
   */
  public static function getFileStorage() {
    return new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
  }
}
