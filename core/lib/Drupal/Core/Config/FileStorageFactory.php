<?php

namespace Drupal\Core\Config;

use Drupal\Core\Site\Settings;

/**
 * Provides a factory for creating config file storage objects.
 */
class FileStorageFactory {

  /**
   * Returns a FileStorage object working with the sync config directory.
   *
   * @return \Drupal\Core\Config\FileStorage FileStorage
   *
   * @throws \Exception
   *   In case the sync directory does not exist or is not defined in
   *   $settings['config_sync_directory'].
   */
  public static function getSync() {
    $directory = Settings::get('config_sync_directory', FALSE);
    if ($directory === FALSE) {
      // @todo: throw a more specific exception.
      // @see https://www.drupal.org/node/2696103
      throw new \Exception('The config sync directory is not defined in $settings["config_sync_directory"]');
    }
    return new FileStorage($directory);
  }

}
