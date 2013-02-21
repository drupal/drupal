<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\SchemaStorage.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageException;

/**
 * Defines the file storage controller for metadata files.
 */
class SchemaStorage extends InstallStorage {

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

}
