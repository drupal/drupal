<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\SchemaStorage.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageException;

/**
 * Defines the file storage controller for metadata files.
 */
class SchemaStorage extends ExtensionInstallStorage {

  /**
   * Implements \Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return array_key_exists($name, $this->getAllFolders());
  }

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::getComponentFolder().
   */
  protected function getComponentFolder($type, $name) {
    return drupal_get_path($type, $name) . '/config/schema';
  }

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::write().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    throw new StorageException('Write operation is not allowed for config schema storage.');
  }

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::delete().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function delete($name) {
    throw new StorageException('Delete operation is not allowed for config schema storage.');
  }

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::rename().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function rename($name, $new_name) {
    throw new StorageException('Rename operation is not allowed for config schema storage.');
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * The list is based on enabled modules and themes.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      parent::getAllFolders();
      $this->folders += $this->getBaseDataTypeSchema();
    }
    return $this->folders;
  }

  /**
   * Gets the base data types for configuration schema.
   *
   * @return array
   *   The file containing the base data types for configuration schema.
   */
  protected function getBaseDataTypeSchema() {
    return array(
      'core.data_types.schema' => 'core/lib/Drupal/Core/Config/Schema'
    );
  }

}
