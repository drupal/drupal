<?php

namespace Drupal\Tests;

use Drupal\Core\Config\ConfigImporter;
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
      $this->configImporter = new ConfigImporter(
        $storage_comparer,
        $this->container->get('event_dispatcher'),
        $this->container->get('config.manager'),
        $this->container->get('lock'),
        $this->container->get('config.typed'),
        $this->container->get('module_handler'),
        $this->container->get('module_installer'),
        $this->container->get('theme_handler'),
        $this->container->get('string_translation')
      );
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
