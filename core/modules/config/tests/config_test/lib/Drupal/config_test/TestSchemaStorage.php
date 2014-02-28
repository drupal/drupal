<?php

/**
 * @file
 * Contains \Drupal\config_test\TestSchemaStorage.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\Schema\SchemaStorage;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Tests configuration schemas of profiles, modules and themes.
 *
 * A test configuration schema storage to read configuration schema from all
 * profiles, modules and themes regardless of installation status or installed
 * profile.
 */
class TestSchemaStorage extends SchemaStorage {

  /**
   * Overrides Drupal\Core\Config\ExtensionInstallStorage::__construct().
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      // @todo Refactor getComponentNames() to use the extension list directly.
      $listing = new ExtensionDiscovery();
      $this->folders = $this->getBaseDataTypeSchema();
      $this->folders += $this->getComponentNames('profile', array_keys($listing->scan('profile')));
      $this->folders += $this->getComponentNames('module', array_keys($listing->scan('module')));
      $this->folders += $this->getComponentNames('theme', array_keys($listing->scan('theme')));
    }
    return $this->folders;
  }

}
