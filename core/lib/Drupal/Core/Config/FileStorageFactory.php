<?php

namespace Drupal\Core\Config;

use Drupal\Core\Site\Settings;

/**
 * Provides a factory for creating config storage objects.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use the
 *   "config.storage.sync" service instead.
 *
 * @see https://www.drupal.org/node/3586484
 */
class FileStorageFactory {

  public function __construct() {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use the "config.storage.sync" service instead. See https://www.drupal.org/node/3586484', E_USER_DEPRECATED);
  }

  /**
   * Returns a StorageInterface object working with the sync config directory.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage object for the configuration sync directory.
   *
   * @throws \Drupal\Core\Config\ConfigDirectoryNotDefinedException
   *   In case the sync directory does not exist or is not defined in
   *   $settings['config_sync_directory'].
   */
  public static function getSync() {
    $directory = Settings::get('config_sync_directory', FALSE);
    if ($directory === FALSE) {
      throw new ConfigDirectoryNotDefinedException('The config sync directory is not defined in $settings["config_sync_directory"]');
    }
    @trigger_error(__CLASS__ . '::getSync() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use the "config.storage.sync" service instead. See https://www.drupal.org/node/3586484', E_USER_DEPRECATED);
    return \Drupal::service('config.storage.sync');
  }

}
