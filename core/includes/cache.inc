<?php

/**
 * @file
 * Functions and interfaces for cache handling.
 */

/**
 * Instantiates and statically caches the correct class for a cache bin.
 *
 * By default, this returns an instance of the DrupalDatabaseCache class.
 * Classes implementing DrupalCacheInterface can register themselves both as a
 * default implementation and for specific bins.
 *
 * @param $bin
 *   The cache bin for which the cache object should be returned, defaults to
 *   'cache'.
 *
 * @return DrupalCacheInterface
 *   The cache object associated with the specified bin.
 *
 * @see DrupalCacheInterface
 */
function cache($bin = 'cache') {
  // Temporary backwards compatibiltiy layer, allow old style prefixed cache
  // bin names to be passed as arguments.
  $bin = str_replace('cache_', '', $bin);

  // We do not use drupal_static() here because we do not want to change the
  // storage of a cache bin mid-request.
  static $cache_objects;
  if (!isset($cache_objects[$bin])) {
    $class = variable_get('cache_class_' . $bin);
    if (!isset($class)) {
      $class = variable_get('cache_default_class', 'DrupalDatabaseCache');
    }
    $cache_objects[$bin] = new $class($bin);
  }
  return $cache_objects[$bin];
}

/**
 * Expires data from the block and page caches.
 */
function cache_clear_all() {
  // @todo: remove before release.
  if (func_get_args()) {
    throw new Exception(t('cache_clear_all() no longer takes arguments, use cache() instead.'));
  }
  // Clear the block cache first, so stale data will
  // not end up in the page cache.
  if (module_exists('block')) {
    cache('block')->expire();
  }
  cache('page')->expire();
}

/**
 * Defines an interface for cache implementations.
 *
 * All cache implementations have to implement this interface.
 * DrupalDatabaseCache provides the default implementation, which can be
 * consulted as an example.
 *
 * To make Drupal use your implementation for a certain cache bin, you have to
 * set a variable with the name of the cache bin as its key and the name of
 * your class as its value. For example, if your implementation of
 * DrupalCacheInterface was called MyCustomCache, the following line would make
 * Drupal use it for the 'cache_page' bin:
 * @code
 *  variable_set('cache_class_cache_page', 'MyCustomCache');
 * @endcode
 *
 * Additionally, you can register your cache implementation to be used by
 * default for all cache bins by setting the variable 'cache_default_class' to
 * the name of your implementation of the DrupalCacheInterface, e.g.
 * @code
 *  variable_set('cache_default_class', 'MyCustomCache');
 * @endcode
 *
 * To implement a completely custom cache bin, use the same variable format:
 * @code
 *  variable_set('cache_class_custom_bin', 'MyCustomCache');
 * @endcode
 * To access your custom cache bin, specify the name of the bin when storing
 * or retrieving cached data:
 * @code
 *  cache_set($cid, $data, 'custom_bin', $expire);
 *  cache_get($cid, 'custom_bin');
 * @endcode
 *
 * @see cache()
 * @see DrupalDatabaseCache
 */
interface DrupalCacheInterface {
  /**
   * Constructs a new cache interface.
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
   *     explicitly told to using cache_clear_all() with a cache ID.
   *   - CACHE_TEMPORARY: Indicates that the item should be removed at the next
   *     general cache wipe.
   *   - A Unix timestamp: Indicates that the item should be kept at least until
   *     the given time, after which it behaves like CACHE_TEMPORARY.
   */
  function set($cid, $data, $expire = CACHE_PERMANENT);

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

/**
 * Defines a stub cache implementation.
 *
 * The stub implementation is needed when database access is not yet available.
 * Because Drupal's caching system never requires that cached data be present,
 * these stub functions can short-circuit the process and sidestep the need for
 * any persistent storage. Using this cache implementation during normal
 * operations would have a negative impact on performance.
 *
 * This also can be used for testing purposes.
 */
class DrupalNullCache implements DrupalCacheInterface {

  /**
   * Constructs a DrupalNullCache object.
   */
  function __construct($bin) {}

  /**
   * Implements DrupalCacheInterface::get().
   */
  function get($cid) {
    return FALSE;
  }

  /**
   * Implements DrupalCacheInterface::getMultiple().
   */
  function getMultiple(&$cids) {
    return array();
  }

  /**
   * Implements DrupalCacheInterface::set().
   */
  function set($cid, $data, $expire = CACHE_PERMANENT) {}

  /**
   * Implements DrupalCacheInterface::delete().
   */
  function delete($cid) {}

  /**
   * Implements DrupalCacheInterface::deleteMultiple().
   */
  function deleteMultiple(array $cids) {}

  /**
   * Implements DrupalCacheInterface::deletePrefix().
   */
  function deletePrefix($prefix) {}

  /**
   * Implements DrupalCacheInterface::flush().
   */
  function flush() {}

  /**
   * Implements DrupalCacheInterface::expire().
   */
  function expire() {}

  /**
   * Implements DrupalCacheInterface::garbageCollection().
   */
  function garbageCollection() {}

  /**
   * Implements DrupalCacheInterface::isEmpty().
   */
  function isEmpty() {
    return TRUE;
  }
}

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 */
class DrupalDatabaseCache implements DrupalCacheInterface {
  protected $bin;

  /**
   * Constructs a new DrupalDatabaseCache object.
   */
  function __construct($bin) {
    // All cache tables should be prefixed with 'cache_', except for the
    // default 'cache' bin.
    if ($bin != 'cache') {
      $bin = 'cache_' . $bin;
    }
    $this->bin = $bin;
  }

  /**
   * Implements DrupalCacheInterface::get().
   */
  function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * Implements DrupalCacheInterface::getMultiple().
   */
  function getMultiple(&$cids) {
    try {
      // Garbage collection necessary when enforcing a minimum cache lifetime.
      $this->garbageCollection($this->bin);

      // When serving cached pages, the overhead of using db_select() was found
      // to add around 30% overhead to the request. Since $this->bin is a
      // variable, this means the call to db_query() here uses a concatenated
      // string. This is highly discouraged under any other circumstances, and
      // is used here only due to the performance overhead we would incur
      // otherwise. When serving an uncached page, the overhead of using
      // db_select() is a much smaller proportion of the request.
      $result = db_query('SELECT cid, data, created, expire, serialized FROM {' . db_escape_table($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids));
      $cache = array();
      foreach ($result as $item) {
        $item = $this->prepareItem($item);
        if ($item) {
          $cache[$item->cid] = $item;
        }
      }
      $cids = array_diff($cids, array_keys($cache));
      return $cache;
    }
    catch (Exception $e) {
      // If the database is never going to be available, cache requests should
      // return FALSE in order to allow exception handling to occur.
      return array();
    }
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return
   *   The item with data unserialized as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache) {
    global $user;

    if (!isset($cache->data)) {
      return FALSE;
    }
    // If enforcing a minimum cache lifetime, validate that the data is
    // currently valid for this user before we return it by making sure the cache
    // entry was created before the timestamp in the current session's cache
    // timer. The cache variable is loaded into the $user object by
    // _drupal_session_read() in session.inc. If the data is permanent or we're
    // not enforcing a minimum cache lifetime always return the cached data.
    if ($cache->expire != CACHE_PERMANENT && variable_get('cache_lifetime', 0) && $user->cache > $cache->created) {
      // This cache data is too old and thus not valid for us, ignore it.
      return FALSE;
    }

    if ($cache->serialized) {
      $cache->data = unserialize($cache->data);
    }

    return $cache;
  }

  /**
   * Implements DrupalCacheInterface::set().
   */
  function set($cid, $data, $expire = CACHE_PERMANENT) {
    $fields = array(
      'serialized' => 0,
      'created' => REQUEST_TIME,
      'expire' => $expire,
    );
    if (!is_string($data)) {
      $fields['data'] = serialize($data);
      $fields['serialized'] = 1;
    }
    else {
      $fields['data'] = $data;
      $fields['serialized'] = 0;
    }

    try {
      db_merge($this->bin)
        ->key(array('cid' => $cid))
        ->fields($fields)
        ->execute();
    }
    catch (Exception $e) {
      // The database may not be available, so we'll ignore these calls.
    }
  }

  /**
   * Implements DrupalCacheInterface::delete().
   */
  function delete($cid) {
    db_delete($this->bin)
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Implements DrupalCacheInterface::deleteMultiple().
   */
  function deleteMultiple(Array $cids) {
    // Delete in chunks when a large array is passed.
    do {
      db_delete($this->bin)
        ->condition('cid', array_splice($cids, 0, 1000), 'IN')
        ->execute();
    }
    while (count($cids));
  }

  /**
   * Implements DrupalCacheInterface::deletePrefix().
   */
  function deletePrefix($prefix) {
    db_delete($this->bin)
      ->condition('cid', db_like($prefix) . '%', 'LIKE')
      ->execute();
  }

  /**
   * Implements DrupalCacheInterface::flush().
   */
  function flush() {
    db_truncate($this->bin)->execute();
  }

  /**
   * Implements DrupalCacheInterface::expire().
   */
  function expire() {
    if (variable_get('cache_lifetime', 0)) {
      // We store the time in the current user's $user->cache variable which
      // will be saved into the sessions bin by _drupal_session_write(). We then
      // simulate that the cache was flushed for this user by not returning
      // cached data that was cached before the timestamp.
      $GLOBALS['user']->cache = REQUEST_TIME;

      $cache_flush = variable_get('cache_flush_' . $this->bin, 0);
      if ($cache_flush == 0) {
        // This is the first request to clear the cache; start a timer.
        variable_set('cache_flush_' . $this->bin, REQUEST_TIME);
      }
      elseif (REQUEST_TIME > ($cache_flush + variable_get('cache_lifetime', 0))) {
        // Clear the cache for everyone; cache_lifetime seconds have passed
        // since the first request to clear the cache.
        db_delete($this->bin)
          ->condition('expire', CACHE_PERMANENT, '<>')
          ->condition('expire', REQUEST_TIME, '<')
          ->execute();
        variable_set('cache_flush_' . $this->bin, 0);
      }
    }
    else {
      // No minimum cache lifetime; flush all temporary cache entries now.
      db_delete($this->bin)
        ->condition('expire', CACHE_PERMANENT, '<>')
        ->condition('expire', REQUEST_TIME, '<')
        ->execute();
    }
  }

  /**
   * Implements DrupalCacheInterface::garbageCollection().
   */
  function garbageCollection() {
    global $user;

    // When cache lifetime is in force, avoid running garbage collection too
    // often since this will remove temporary cache items indiscriminately.
    $cache_flush = variable_get('cache_flush_' . $this->bin, 0);
    if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= REQUEST_TIME)) {
      // Reset the variable immediately to prevent a meltdown in heavy load
      // situations.
      variable_set('cache_flush_' . $this->bin, 0);
      // Time to flush old cache data
      db_delete($this->bin)
        ->condition('expire', CACHE_PERMANENT, '<>')
        ->condition('expire', $cache_flush, '<=')
        ->execute();
    }
  }

  /**
   * Implements DrupalCacheInterface::isEmpty().
   */
  function isEmpty() {
    $this->garbageCollection();
    $query = db_select($this->bin);
    $query->addExpression('1');
    $result = $query->range(0, 1)
      ->execute()
      ->fetchField();
    return empty($result);
  }
}
