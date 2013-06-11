<?php

/**
 * @file
 * Contains Drupal\Core\Config\BootstrapConfigStorageFactory.
 */

namespace Drupal\Core\Config;
use Drupal\Component\Utility\Settings;

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
    $settings = Settings::getSingleton();
    $drupal_bootstrap_config_storage = $settings->get('drupal_bootstrap_config_storage');
    if ($drupal_bootstrap_config_storage && is_callable($drupal_bootstrap_config_storage)) {
      return call_user_func($drupal_bootstrap_config_storage);
    }
    else {
      return new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
    }
  }

}
