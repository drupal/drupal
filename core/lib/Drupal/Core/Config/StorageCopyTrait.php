<?php

namespace Drupal\Core\Config;

/**
 * Utility trait to copy configuration from one storage to another.
 */
trait StorageCopyTrait {

  /**
   * Copy the configuration from one storage to another and remove stale items.
   *
   * This method makes sure the target storage contains the same config as the
   * source storage. It may empty the target storage and copy all collections
   * from the source storage or it may just update config which is different.
   * Configuration is only copied and not imported, this method should not be
   * used with the active storage as the target.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   */
  protected static function replaceStorageContents(StorageInterface $source, StorageInterface &$target) {
    // Make sure that the target is set to the same collection as the source.
    if ($source->getCollectionName() !== $target->getCollectionName()) {
      $target = $target->createCollection($source->getCollectionName());
    }

    // Remove only config in collections that don't exist in the source.
    foreach (array_diff($target->getAllCollectionNames(), $source->getAllCollectionNames()) as $collection) {
      $target->createCollection($collection)->deleteAll();
    }

    $comparer = NULL;
    if (static::shouldUseStorageComparer($source, $target)) {
      // At least one of the collections will benefit from the StorageComparer.
      $comparer = new StorageComparer($source, $target);
      $comparer->createChangelist();
    }

    if ($comparer instanceof StorageComparer && !$comparer->hasChanges()) {
      // The best case is to do nothing when the storages are equal already.
      return;
    }

    // Copy all the configuration from all the collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
      $source_collection = $source->createCollection($collection);
      $target_collection = $target->createCollection($collection);

      if ($comparer instanceof StorageComparer && static::shouldUseStorageComparer($source, $target, $collection)) {
        // It is better to update only what is different.
        foreach ($comparer->getChangelist('delete', $collection) as $config_name_to_delete) {
          $target_collection->delete($config_name_to_delete);
        }

        foreach ($comparer->getChangelist('rename', $collection) as $rename) {
          list($old_name, $new_name) = explode('::', $rename);
          $target_collection->rename($old_name, $new_name);
        }

        $names_to_write = array_merge(
          $comparer->getChangelist('create', $collection),
          $comparer->getChangelist('update', $collection)
        );
        $source_config = $source_collection->readMultiple($names_to_write);
        foreach ($names_to_write as $name) {
          if ($source_config[$name] !== FALSE) {
            $target_collection->write($name, $source_config[$name]);
          }
          else {
            \Drupal::logger('config')->notice('Missing required data for configuration: %config', [
              '%config' => $name,
            ]);
          }
        }
      }
      else {
        // To clear everything and write everything new is more efficient
        // when a lot of the config changes.
        $target_collection->deleteAll();
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
    }

  }

  /**
   * Decide if using a StorageComparer is beneficial.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   * @param string|null $collection
   *   The collection name to check, null to check all collections.
   * @param bool $optimistic
   *   True for when the StorageComparer is already set up anyway.
   *
   * @return bool
   *   Whether or not to use the StorageComparer for making the storages equal.
   */
  protected static function shouldUseStorageComparer(StorageInterface $source, StorageInterface $target, string $collection = NULL, bool $optimistic = TRUE): bool {
    if ($collection === NULL) {
      // Check if one of the collections would benefit from the comparer.
      foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $name) {
        // When we check if the comparer should be used at all we check pessimistically.
        if (static::shouldUseStorageComparer($source, $target, $name, FALSE)) {
          return TRUE;
        }
      }
      return FALSE;
    }

    $target_size = count($target->createCollection($collection)->listAll());
    if ($target_size === 0) {
      return FALSE;
    }

    $source_size = count($source->createCollection($collection)->listAll());

    // @todo find out what factor to use.
    $factor = $optimistic ? 1.5 : 1.2;

    // We compare the amount of config which exists in the storage.
    // This is not accurate, but just a guess. The rationale is that if the size varies a lot, then deleting everything
    // and writing everything again is better than the overhead of checking what actually changed.
    return $source_size < $target_size * $factor && $target_size < $source_size * $factor;
  }

}
