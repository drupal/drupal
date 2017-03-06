<?php

namespace Drupal\Core\Config;

/**
 * Provides a factory for creating config file storage objects.
 */
class FileStorageFactory {

  /**
   * Returns a FileStorage object working with the active config directory.
   *
   * @return \Drupal\Core\Config\FileStorage FileStorage
   *
   * @deprecated in Drupal 8.0.x and will be removed before 9.0.0. Drupal core
   * no longer creates an active directory.
   */
  public static function getActive() {
    return new FileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
  }

  /**
   * Returns a FileStorage object working with the sync config directory.
   *
   * @return \Drupal\Core\Config\FileStorage FileStorage
   */
  public static function getSync() {
    return new FileStorage(config_get_config_directory(CONFIG_SYNC_DIRECTORY));
  }

}
