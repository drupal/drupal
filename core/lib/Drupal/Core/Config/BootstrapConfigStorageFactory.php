<?php

/**
 * @file
 * Contains Drupal\Core\Config\BootstrapConfigStorageFactory.
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
   * @return \Drupal\Core\Config\StorageInterface
   *   A configuration storage implementation.
   */
  public static function get() {
    $bootstrap_config_storage = Settings::get('bootstrap_config_storage');
    if (!empty($bootstrap_config_storage) && is_callable($bootstrap_config_storage)) {
      return call_user_func($bootstrap_config_storage);
    }
    // Fallback to the DatabaseStorage.
    return self::getDatabaseStorage();
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
