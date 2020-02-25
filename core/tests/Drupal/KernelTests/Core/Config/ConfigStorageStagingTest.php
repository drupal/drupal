<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the service "config.storage.staging" has been deprecated.
 *
 * @group Test
 * @group legacy
 */
class ConfigStorageStagingTest extends KernelTestBase {

  /**
   * @expectedDeprecation The "config.storage.staging" service is deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use the "config.storage.sync" service instead. See https://www.drupal.org/node/2574957
   */
  public function testConfigStorageStagingDeprecation() {
    $storage_staging = \Drupal::service('config.storage.staging');
    // Ensure at least one assertion.
    $this->assertInstanceOf(FileStorage::class, $storage_staging);
  }

}
