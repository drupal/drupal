<?php

namespace Drupal\Core\Config;

/**
 * Utility trait to copy configuration from one storage to another.
 */
trait StorageCopyTrait {

  /**
   * Copy the configuration from one storage to another and remove stale items.
   *
   * This method empties target storage and copies all collections from source.
   * Configuration is only copied and not imported, should not be used
   * with the active storage as the target.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   */
  protected static function replaceStorageContents(StorageInterface $source, StorageInterface &$target) {
    // Make sure there is no stale configuration in the target storage.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $target->getAllCollectionNames()) as $collection) {
      $target->createCollection($collection)->deleteAll();
    }

    // Copy all the configuration from all the collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
      $source_collection = $source->createCollection($collection);
      $target_collection = $target->createCollection($collection);
      foreach ($source_collection->listAll() as $name) {
        $data = $source_collection->read($name);
        if ($data !== FALSE) {
          $target_collection->write($name, $data);
        }
        else {
          \Drupal::logger('config')->notice('Missing required data for configuration: %config', [
            '%config' => $name,
          ]);
        }
      }
    }

    // Make sure that the target is set to the same collection as the source.
    $target = $target->createCollection($source->getCollectionName());
  }

}
