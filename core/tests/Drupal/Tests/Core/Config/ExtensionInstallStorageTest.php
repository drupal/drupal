<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\ExtensionInstallStorage
 * @group Config
 */
class ExtensionInstallStorageTest extends UnitTestCase {

  /**
   * @covers ::createCollection
   */
  public function testCreateCollection() {
    $memory = new MemoryStorage();
    $include_profile = FALSE;
    $profile = $this->randomMachineName();
    $collectionName = $this->randomMachineName();

    // Set up the storage.
    $storage = new ExtensionInstallStorage($memory, InstallStorage::CONFIG_INSTALL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION, $include_profile, $profile);
    // Create a collection.
    $collection = $storage->createCollection($collectionName);

    static::assertEquals($collectionName, $collection->getCollectionName());
  }

}
