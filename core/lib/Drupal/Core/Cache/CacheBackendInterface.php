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
 *  $conf['cache_classes']['cache_page'] = 'MyCustomCache';
 * @endcode
 *
 * Additionally, you can register your cache implementation to be used by
 * default for all cache bins by setting the $conf['cache_classes'] variable and
 * changing the value of the 'cache' key to the name of your implementation of
 * the Drupal\Core\Cache\CacheBackendInterface, e.g.
 * @code
 *  $conf['cache_classes']['cache'] = 'MyCustomCache';
 * @endcode
 *
 * To implement a completely custom cache bin, use the same variable format:
 * @code
 *  $conf['cache_classes']['custom_bin'] = 'MyCustomCache';
 * @endcode
 * To access your custom cache bin, specify the name of the bin when storing
 * or retrieving cached data:
 * @code
 *  cache_set($cid, $data, 'custom_bin', $expire);
 *  cache_get($cid, 'custom_bin');
 * @endcode
 *
 * @see cache()
 * @see Drupal\Core\Cache\DatabaseBackend
 */
interface CacheBackendInterface {

  /**
   * Constructs a new cache backend.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   */
  function __construct($bin);

  /**
   * Returns data from the persistent cache.
   *
   * Data may be stored as either plain text or as serialized data. cache_get()
   * will automatically return unserialized objects and arrays.
   *
   * @param $cid
   *   The cache ID of the data to retrieve.
   *
   * @return
   *   The cache or FALSE on failure.
   */
  function get($cid);

  /**
   * Returns data from the persistent cache when given an array of cache IDs.
   *
   * @param $cids
   *   An array of cache IDs for the data to retrieve. This is passed by
   *   reference, and will have the IDs successfully returned from cache
   *   removed.
   *
   * @return
   *   An array of the items successfully returned from cache indexed by cid.
   */
  function getMultiple(&$cids);

  /**
   * Stores data in the persistent cache.
   *
   * @param $cid
   *   The cache ID of the data to store.
   * @param $data
   *   The data to store in the cache. Complex data types will be automatically
   *   serialized before insertion.
   *   Strings will be stored as plain text and not serialized.
   * @param $expire
   *   One of the following values:
   *   - CACHE_PERMANENT: Indicates that the item should never be removed unless
   *     explicitly told to using cache->delete($cid).
   *   - A Unix timestamp: Indicates that the item should be kept at least until
   *     the given time.
   * @param array $tags
   *   An array of tags to be stored with the cache item. These should normally
   *   identify objects used to build the cache item, which should trigger
   *   cache invalidation when updated. For example if a cached item represents
   *   a node, both the node ID and the author's user ID might be passed in as
   *   tags. For example array('node' => array(123), 'user' => array(92)).
   */
  function set($cid, $data, $expire = CACHE_PERMANENT, array $tags = array());

  /**
   * Deletes an item from the cache.
   *
   * @param $cid
   *    The cache ID to delete.
   */
  function delete($cid);

  /**
   * Deletes multiple items from the cache.
   *
   * @param $cids
   *   An array of $cids to delete.
   */
  function deleteMultiple(Array $cids);

  /**
   * Deletes items from the cache using a wildcard prefix.
   *
   * @param $prefix
   *   A wildcard prefix.
   */
  function deletePrefix($prefix);

  /**
   * Flushes all cache items in a bin.
   */
  function flush();

  /**
   * Expires temporary items from the cache.
   */
  function expire();

  /**
   * Invalidates each tag in the $tags array.
   *
   * @param array $tags
   *   Associative array of tags, in the same format that is passed to
   *   CacheBackendInterface::set().
   *
   * @see CacheBackendInterface::set()
   */
  function invalidateTags(array $tags);

  /**
   * Performs garbage collection on a cache bin.
   */
  function garbageCollection();

  /**
   * Checks if a cache bin is empty.
   *
   * A cache bin is considered empty if it does not contain any valid data for
   * any cache ID.
   *
   * @return
   *   TRUE if the cache bin specified is empty.
   */
  function isEmpty();
}
