<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides helper methods to deal with config system objects in tests.
 */
trait ConfigTestTrait {

  use StorageCopyTrait;

  /**
   * Returns a ConfigImporter object to import test configuration.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The config importer object.
   */
  protected function configImporter() {
    if (!$this->configImporter) {
      // Set up the ConfigImporter object for testing.
      $storage_comparer = new StorageComparer(
        $this->container->get('config.storage.sync'),
        $this->container->get('config.storage')
      );
      $this->configImporter = $this->container->get(ConfigImporterFactory::class)->get($storage_comparer);
    }
    // Always recalculate the changelist when called.
    return $this->configImporter->reset();
  }

  /**
   * Copies configuration objects from source storage to target storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source config storage service.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target config storage service.
   */
  protected function copyConfig(StorageInterface $source_storage, StorageInterface $target_storage) {
    static::replaceStorageContents($source_storage, $target_storage);
  }

}
