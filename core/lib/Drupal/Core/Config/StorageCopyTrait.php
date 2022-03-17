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
    // Remove all collections from the target which are not in the source.
    foreach (array_diff($target->getAllCollectionNames(), $source->getAllCollectionNames()) as $collection) {
      // We do this first so we don't have to loop over the added collections.
      $target->createCollection($collection)->deleteAll();
    }
    // Copy all the configuration from all the collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
      $source_collection = $source->createCollection($collection);
      $target_collection = $target->createCollection($collection);
      $names = $source_collection->listAll();
      // First we delete all the config which shouldn't be in the target.
      foreach (array_diff($target_collection->listAll(), $names) as $name) {
        $target_collection->delete($name);
      }
      // Then we loop over the config which needs to be there.
      foreach ($names as $name) {
        $data = $source_collection->read($name);
        if ($data !== FALSE) {
          if ($target_collection->read($name) !== $data) {
            // Update the target collection if the data is different.
            $target_collection->write($name, $data);
          }
        }
        else {
          $target_collection->delete($name);
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
