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
 * To make Drupal use your implementation for a certain cache bin, you have to
 * set a variable with the name of the cache bin as its key and the name of
 * your class as its value. For example, if your implementation of
 * Drupal\Core\Cache\CacheBackendInterface was called MyCustomCache, the
 * following line would make Drupal use it for the 'cache_page' bin:
 * @code
 *  $settings['cache_classes']['cache_page'] = 'MyCustomCache';
 * @endcode
 *
 * Additionally, you can register your cache implementation to be used by
 * default for all cache bins by setting the $settings['cache_classes'] variable and
 * changing the value of the 'cache' key to the name of your implementation of
 * the Drupal\Core\Cache\CacheBackendInterface, e.g.
 * @code
 *  $settings['cache_classes']['cache'] = 'MyCustomCache';
 * @endcode
 *
 * To implement a completely custom cache bin, use the same variable format:
 * @code
 *  $settings['cache_classes']['custom_bin'] = 'MyCustomCache';
 * @endcode
 * To access your custom cache bin, specify the name of the bin when storing
 * or retrieving cached data:
 * @code
 *  \Drupal::cache('custom_bin')->set($cid, $data, $expire);
 *  \Drupal::cache('custom_bin')->get($cid);
 * @endcode
 *
 * There are two ways to "remove" a cache item:
 * - Deletion (using delete(), deleteMultiple(), deleteTags() or deleteAll()):
 *   Permanently removes the item from the cache.
 * - Invalidation (using invalidate(), invalidateMultiple(), invalidateTags()
 *   or invalidateAll()): a "soft" delete that only marks the items as
 *   "invalid", meaning "not fresh" or "not fresh enough". Invalid items are
 *   not usually returned from the cache, so in most ways they behave as if they
 *   have been deleted. However, it is possible to retrieve the invalid entries,
 *   if they have not yet been permanently removed by the garbage collector, by
 *   passing TRUE as the second argument for get($cid, $allow_invalid).
 *
 * Cache items should be deleted if they are no longer considered useful. This
 * is relevant e.g. if the cache item contains references to data that has been
 * deleted. On the other hand, it may be relevant to just invalidate the item
 * if the cached data may be useful to some callers until the cache item has
 * been updated with fresh data. The fact that it was fresh a short while ago
 * may often be sufficient.
 *
 * Invalidation is particularly useful to protect against stampedes. Rather than
 * having multiple concurrent requests updating the same cache item when it
 * expires or is deleted, there can be one request updating the cache, while
 * the other requests can proceed using the stale value. As soon as the cache
 * item has been updated, all future requests will use the updated value.
 *
 * @see \Drupal::cache()
 * @see \Drupal\Core\Cache\DatabaseBackend
 */
interface CacheBackendInterface {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   */
  const CACHE_PERMANENT = 0;

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

  /**
   * Checks if a cache bin is empty.
   *
   * A cache bin is considered empty if it does not contain any valid data for
   * any cache ID.
   *
   * @return
   *   TRUE if the cache bin specified is empty.
   */
  public function isEmpty();
}
