<?php
// $Id: cache.inc,v 1.3 2006/07/05 11:45:51 dries Exp $

/**
 * Return data from the persistent cache.
 *
 * @param $key
 *   The cache ID of the data to retrieve.
 */
function cache_get($key) {
  global $user;

  // Garbage collection necessary when enforcing a minimum cache lifetime
  $cache_flush = variable_get('cache_flush', 0);
  if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= time())) {
    // Time to flush old cache data
    db_query("DELETE FROM {cache} WHERE expire != %d AND expire <= %d", CACHE_PERMANENT, $cache_flush);
    variable_set('cache_flush', 0);
  }

  $cache = db_fetch_object(db_query("SELECT data, created, headers, expire FROM {cache} WHERE cid = '%s'", $key));
  if (isset($cache->data)) {
    // If the data is permanent or we're not enforcing a minimum cache lifetime
    // always return the cached data.
    if ($cache->expire == CACHE_PERMANENT || !variable_get('cache_lifetime', 0)) {
      $cache->data = db_decode_blob($cache->data);
    }
    // If enforcing a minimum cache lifetime, validate that the data is
    // currently valid for this user before we return it by making sure the
    // cache entry was created before the timestamp in the current session's
    // cache timer. The cache variable is loaded into the $user object by
    // sess_read() in session.inc.
    else {
      if ($user->cache > $cache->created) {
        // This cache data is too old and thus not valid for us, ignore it.
        return 0;
      }
      else {
        $cache->data = db_decode_blob($cache->data);
      }
    }
    return $cache;
  }
  return 0;
}

/**
 * Store data in the persistent cache.
 *
 * @param $cid
 *   The cache ID of the data to store.
 * @param $data
 *   The data to store in the cache. Complex data types must be serialized first.
 * @param $expire
 *   One of the following values:
 *   - CACHE_PERMANENT: Indicates that the item should never be removed unless
 *     explicitly told to using cache_clear_all() with a cache ID.
 *   - CACHE_TEMPORARY: Indicates that the item should be removed at the next
 *     general cache wipe.
 *   - A Unix timestamp: Indicates that the item should be kept at least until
 *     the given time, after which it behaves like CACHE_TEMPORARY.
 * @param $headers
 *   A string containing HTTP header information for cached pages.
 */
function cache_set($cid, $data, $expire = CACHE_PERMANENT, $headers = NULL) {
  db_lock_table('cache');
  db_query("UPDATE {cache} SET data = %b, created = %d, expire = %d, headers = '%s' WHERE cid = '%s'", $data, time(), $expire, $headers, $cid);
  if (!db_affected_rows()) {
    @db_query("INSERT INTO {cache} (cid, data, created, expire, headers) VALUES ('%s', %b, %d, %d, '%s')", $cid, $data, time(), $expire, $headers);
  }
  db_unlock_tables();
}

/**
 * Expire data from the cache.
 *
 * @param $cid
 *   If set, the cache ID to delete. Otherwise, all cache entries that can
 *   expire are deleted.
 *
 * @param $wildcard
 *   If set to TRUE, the $cid is treated as a substring to match rather than a
 *   complete ID.
 */
function cache_clear_all($cid = NULL, $wildcard = FALSE) {
  global $user;

  if (empty($cid)) {
    if (variable_get('cache_lifetime', 0)) {
      // We store the time in the current user's $user->cache variable which
      // will be saved into the sessions table by sess_write(). We then
      // simulate that the cache was flushed for this user by not returning
      // cached data that was cached before the timestamp.
      $user->cache = time();

      $cache_flush = variable_get('cache_flush', 0);
      if ($cache_flush == 0) {
        // This is the first request to clear the cache, start a timer.
        variable_set('cache_flush', time());
      }
      else if (time() > ($cache_flush + variable_get('cache_lifetime', 0))) {
        // Clear the cache for everyone, cache_flush_delay seconds have
        // passed since the first request to clear the cache.
        db_query("DELETE FROM {cache} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
        variable_set('cache_flush', 0);
      }
    }
    else {
      // No minimum cache lifetime, flush all temporary cache entries now.
      db_query("DELETE FROM {cache} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
    }
  }
  else {
    if ($wildcard) {
      db_query("DELETE FROM {cache} WHERE cid LIKE '%%%s%%'", $cid);
    }
    else {
      db_query("DELETE FROM {cache} WHERE cid = '%s'", $cid);
    }
  }
}

