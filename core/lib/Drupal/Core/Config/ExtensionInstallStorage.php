<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ExtensionInstallStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageException;

/**
 * Defines the file storage controller for metadata files.
 */
class ExtensionInstallStorage extends InstallStorage {

  /**
   * The active configuration store.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::__construct().
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   */
  public function __construct(StorageInterface $config_storage) {
    $this->configStorage = $config_storage;
  }

  /**
   * Resets the static cache.
   */
  public function reset() {
    $this->folders = NULL;
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * The list is based on enabled modules and themes. The active configuration
   * storage is used rather than \Drupal\Core\Extension\ModuleHandler and
   *  \Drupal\Core\Extension\ThemeHandler in order to resolve circular
   * dependencies between these services and \Drupal\Core\Config\ConfigInstaller
   * and \Drupal\Core\Config\TypedConfigManager.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = array();
      $modules = $this->configStorage->read('system.module');
      if (isset($modules['enabled'])) {
        $this->folders += $this->getComponentNames('module', array_keys($modules['enabled']));
      }
      $themes = $this->configStorage->read('system.theme');
      if (isset($themes['enabled'])) {
        $this->folders += $this->getComponentNames('theme', array_keys($themes['enabled']));
      }
    }
    return $this->folders;
  }
}

