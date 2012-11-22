<?php

/**
 * @file
 * Contains Drupal\Core\Config\BootstrapConfigStorageFactory.
 */

namespace Drupal\Core\Config;


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
    if (isset($GLOBALS['conf']['drupal_bootstrap_config_storage'])) {
      return call_user_func($GLOBALS['conf']['drupal_bootstrap_config_storage']);
    }
    else {
      return new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
    }
  }

}
