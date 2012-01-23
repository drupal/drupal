<?php

/**
 * @file
 * Definition of DatabaseBackend.
 */

namespace Drupal\Core\Cache;

use Exception;

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 */
class DatabaseBackend implements CacheBackendInterface {

  /**
   * @var string
   */
  protected $bin;

  /**
   * Implements Drupal\Cache\CacheBackendInterface::__construct().
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
   * Implements Drupal\Cache\CacheBackendInterface::get().
   */
  function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::getMultiple().
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
   * @param stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return mixed
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
   * Implements Drupal\Cache\CacheBackendInterface::set().
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
      // The database may not be available, so we'll ignore cache_set requests.
    }
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::delete().
   */
  function delete($cid) {
    db_delete($this->bin)
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::deleteMultiple().
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
   * Implements Drupal\Cache\CacheBackendInterface::deletePrefix().
   */
  function deletePrefix($prefix) {
    db_delete($this->bin)
      ->condition('cid', db_like($prefix) . '%', 'LIKE')
      ->execute();
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::flush().
   */
  function flush() {
    db_truncate($this->bin)->execute();
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::expire().
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
        // This is the first request to clear the cache, start a timer.
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
      // No minimum cache lifetime, flush all temporary cache entries now.
      db_delete($this->bin)
        ->condition('expire', CACHE_PERMANENT, '<>')
        ->condition('expire', REQUEST_TIME, '<')
        ->execute();
    }
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::garbageCollection().
   */
  function garbageCollection() {
    global $user;

    // When cache lifetime is in force, avoid running garbage collection too
    // often since this will remove temporary cache items indiscriminately.
    $cache_flush = variable_get('cache_flush_' . $this->bin, 0);
    if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= REQUEST_TIME)) {
      // Reset the variable immediately to prevent a meltdown in heavy load situations.
      variable_set('cache_flush_' . $this->bin, 0);
      // Time to flush old cache data
      db_delete($this->bin)
        ->condition('expire', CACHE_PERMANENT, '<>')
        ->condition('expire', $cache_flush, '<=')
        ->execute();
    }
  }

  /**
   * Implements Drupal\Cache\CacheBackendInterface::isEmpty().
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
