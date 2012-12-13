<?php

/**
 * @file
 * Contains Drupal\Core\Config\InstallStorage.
 */

namespace Drupal\Core\Config;

/**
 * Storage controller used by the Drupal installer.
 *
 * @see install_begin_request()
 */
class InstallStorage extends FileStorage {

  /**
   * Overrides Drupal\Core\Config\FileStorage::__construct().
   */
  public function __construct() {
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::getFilePath().
   *
   * Returns the path to the configuration file.
   *
   * Determines the owner and path to the default configuration file of a
   * requested config object name located in the installation profile, a module,
   * or a theme (in this order).
   *
   * @return string
   *   The path to the configuration file.
   *
   * @todo Improve this when figuring out how we want to handle configuration in
   *   installation profiles. E.g., a config object actually has to be searched
   *   in the profile first (whereas the profile is never the owner), only
   *   afterwards check for a corresponding module or theme.
   */
  public function getFilePath($name) {
    // Extract the owner.
    $owner = strtok($name, '.');
    // Determine the path to the owner.
    $path = FALSE;
    foreach (array('profile', 'module', 'theme') as $type) {
      if ($path = drupal_get_path($type, $owner)) {
        $file = $path . '/config/' . $name . '.' . static::getFileExtension();
        if (file_exists($file)) {
          return $file;
        }
      }
    }
    // If any code in the early installer requests a configuration object that
    // does not exist anywhere as default config, then that must be mistake.
    throw new StorageException(format_string('Missing configuration file: @name', array(
      '@name' => $name,
    )));
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::write().
   *
   * @throws Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    throw new StorageException('Write operation is not allowed during install.');
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::delete().
   *
   * @throws Drupal\Core\Config\StorageException
   */
  public function delete($name) {
    throw new StorageException('Delete operation is not allowed during install.');
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::rename().
   *
   * @throws Drupal\Core\Config\StorageException
   */
  public function rename($name, $new_name) {
    throw new StorageException('Rename operation is not allowed during install.');
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   *
   * @throws Drupal\Core\Config\StorageException
   */
  public function listAll($prefix = '') {
    throw new StorageException('List operation is not allowed during install.');
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::deleteAll().
   *
   * @throws Drupal\Core\Config\StorageException
   */
  public function deleteAll($prefix = '') {
    throw new StorageException('Delete operation is not allowed during install.');
  }

}
