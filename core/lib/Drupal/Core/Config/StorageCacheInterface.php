<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageCacheInterface.
 */

namespace Drupal\Core\Config;

/**
 * Defines an interface for cached configuration storage.
 */
interface StorageCacheInterface {

  /**
   * Cache tag.
   *
   * Used by Drupal\Core\Config\CachedStorage::findByPrefix so that cached items
   * can be cleared during writes, deletes and renames.
   */
  const FIND_BY_PREFIX_CACHE_TAG = 'configFindByPrefix';

  /**
   * Reset the static cache of the listAll() cache.
   */
  public function resetListCache();

}
