<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\CacheBackendInterface.
 */

namespace Drupal\Core\Cache;

/**
 * Defines an interface for cache implementations.
 *
 * All cache implementations have to implement this interface.
 * Drupal\Core\Cache\DatabaseBackend provides the default implementation, which
 * can be consulted as an example.
 *
 * The cache indentifiers are case sensitive.
 *
 * @ingroup cache
 */
interface CacheBackendInterface {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   */
  const CACHE_PERMANENT = -1;

  /**
   * Returns data from the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   * @param bool $allow_invalid
   *   (optional) If TRUE, a cache item may be returned even if it is expired or
   *   has been invalidated. Such items may sometimes be preferred, if the
   *   alternative is recalculating the value stored in the cache, especially
   *   if another concurrent request is already recalculating the same value.
   *   The "valid" property of the returned object indicates whether the item is
   *   valid or not. Defaults to FALSE.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
   */
  public function get($cid, $allow_invalid = FALSE);

  /**
   * Returns data from the persistent cache when given an array of cache IDs.
   *
   * @param array $cids
   *   An array of cache IDs for the data to retrieve. This is passed by
   *   reference, and will have the IDs successfully returned from cache
   *   removed.
   * @param bool $allow_invalid
   *   (optional) If TRUE, cache items may be returned even if they have expired
   *   or been invalidated. Such items may sometimes be preferred, if the
   *   alternative is recalculating the value stored in the cache, especially
   *   if another concurrent thread is already recalculating the same value. The
   *   "valid" property of the returned objects indicates whether the items are
   *   valid or not. Defaults to FALSE.
   *
   * @return array
   *   An array of cache item objects indexed by cache ID.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE);

  /**
   * Stores data in the persistent cache.
   *
   * Core cache implementations set the created time on cache item with
   * microtime(TRUE) rather than REQUEST_TIME_FLOAT, because the created time
   * of cache items should match when they are created, not when the request
   * started. Apart from being more accurate, this increases the chance an
   * item will legitimately be considered valid.
   *
   * @param string $cid
   *   The cache ID of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   *   Some storage engines only allow objects up to a maximum of 1MB in size to
   *   be stored by default. When caching large arrays or similar, take care to
   *   ensure $data does not exceed this size.
   * @param int $expire
   *   One of the following values:
   *   - CacheBackendInterface::CACHE_PERMANENT: Indicates that the item should
   *     not be removed unless it is deleted explicitly.
   *   - A Unix timestamp: Indicates that the item will be considered invalid
   *     after this time, i.e. it will not be returned by get() unless
   *     $allow_invalid has been set to TRUE. When the item has expired, it may
   *     be permanently deleted by the garbage collector at any time.
   * @param array $tags
   *   An array of tags to be stored with the cache item. These should normally
   *   identify objects used to build the cache item, which should trigger
   *   cache invalidation when updated. For example if a cached item represents
   *   a node, both the node ID and the author's user ID might be passed in as
   *   tags. For example array('node' => array(123), 'user' => array(92)).
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   * @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array());

  /**
   * Store multiple items in the persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid. In the form:
   *   @code
   *   $items = array(
   *     $cid => array(
   *       // Required, will be automatically serialized if not a string.
   *       'data' => $data,
   *       // Optional, defaults to CacheBackendInterface::CACHE_PERMANENT.
   *       'expire' => CacheBackendInterface::CACHE_PERMANENT,
   *       // (optional) The cache tags for this item, see CacheBackendInterface::set().
   *       'tags' => array(),
   *     ),
   *   );
   *   @endcode
   */
  public function setMultiple(array $items);

  /**
   * Deletes an item from the cache.
   *
   * If the cache item is being deleted because it is no longer "fresh", you may
   * consider using invalidate() instead. This allows callers to retrieve the
   * invalid item by calling get() with $allow_invalid set to TRUE. In some cases
   * an invalid item may be acceptable rather than having to rebuild the cache.
   *
   * @param string $cid
   *   The cache ID to delete.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteAll()
   */
  public function delete($cid);

  /**
   * Deletes multiple items from the cache.
   *
   * If the cache items are being deleted because they are no longer "fresh",
   * you may consider using invalidateMultiple() instead. This allows callers to
   * retrieve the invalid items by calling get() with $allow_invalid set to TRUE.
   * In some cases an invalid item may be acceptable rather than having to
   * rebuild the cache.
   *
   * @param array $cids
   *   An array of cache IDs to delete.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteAll()
   */
  public function deleteMultiple(array $cids);

  /**
   * Deletes items with any of the specified tags.
   *
   * If the cache items are being deleted because they are no longer "fresh",
   * you may consider using invalidateTags() instead. This allows callers to
   * retrieve the invalid items by calling get() with $allow_invalid set to TRUE.
   * In some cases an invalid item may be acceptable rather than having to
   * rebuild the cache.
   *
   * @param array $tags
   *   Associative array of tags, in the same format that is passed to
   *   CacheBackendInterface::set().
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteAll()
   */
  public function deleteTags(array $tags);

  /**
   * Deletes all cache items in a bin.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteTags()
   */
  public function deleteAll();

  /**
   * Marks a cache item as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the $allow_invalid
   * argument is TRUE.
   *
   * @param string $cid
   *   The cache ID to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidate($cid);

  /**
   * Marks cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the $allow_invalid
   * argument is TRUE.
   *
   * @param string $cids
   *   An array of cache IDs to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidateMultiple(array $cids);

  /**
   * Marks cache items with any of the specified tags as invalid.
   *
   * @param array $tags
   *   Associative array of tags, in the same format that is passed to
   *   CacheBackendInterface::set().
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteTags()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidateTags(array $tags);

  /**
   * Marks all cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the $allow_invalid
   * argument is TRUE.
   *
   * @param string $cids
   *   An array of cache IDs to invalidate.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::deleteAll()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple()
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   */
  public function invalidateAll();

  /**
   * Performs garbage collection on a cache bin.
   *
   * The backend may choose to delete expired or invalidated items.
   */
  public function garbageCollection();

  /**
   * Remove a cache bin.
   */
  public function removeBin();
}
