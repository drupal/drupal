<?php

/**
 * @file
 * Contains \Drupal\config_test\TestInstallStorage.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\InstallStorage;

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
      $this->folders = $this->getComponentNames('profile', array_keys(drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.profile$/', 'profiles')));
      $this->folders += $this->getComponentNames('module', array_keys(drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.module$/', 'modules', 'name', 0)));
      $this->folders += $this->getComponentNames('theme', array_keys(drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.info.yml$/', 'themes')));
    }
    return $this->folders;
  }

}
