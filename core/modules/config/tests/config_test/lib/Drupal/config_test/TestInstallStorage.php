<?php

/**
 * @file
 * Contains \Drupal\config_test\TestInstallStorage.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Tests configuration of profiles, modules and themes.
 *
 * A test configuration storage to read configuration from all profiles, modules
 * and themes regardless of installation status or installed profile.
 */
class TestInstallStorage extends InstallStorage {

  /**
   * {@inheritdoc}
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      // @todo Refactor getComponentNames() to use the extension list directly.
      $listing = new ExtensionDiscovery();
      $this->folders = $this->getComponentNames('profile', array_keys($listing->scan('profile')));
      $this->folders += $this->getComponentNames('module', array_keys($listing->scan('module')));
      $this->folders += $this->getComponentNames('theme', array_keys($listing->scan('theme')));
    }
    return $this->folders;
  }

}
