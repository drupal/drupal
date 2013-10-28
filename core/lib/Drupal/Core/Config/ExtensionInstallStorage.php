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
   * Returns a map of all config object names and their folders.
   *
   * The list is based on enabled modules and themes.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = $this->getComponentNames('module', array_keys(\Drupal::moduleHandler()->getModuleList()));
      $this->folders += $this->getComponentNames('theme', array_keys(array_filter(list_themes(), function ($theme) {return $theme->status;})));
    }
    return $this->folders;
  }
}

