<?php
// $Id$

/**
 * Return data from the persistent cache. Data may be stored as either plain text or as serialized data.
 * cache_get will automatically return unserialized objects and arrays.
 *
 * @param $cid
 *   The cache ID of the data to retrieve.
 * @param $table
 *   The table $table to store the data in. Valid core values are 'cache_filter',
 *   'cache_menu', 'cache_page', or 'cache' for the default cache.
 */
function cache_get($cid, $table = 'cache') {
  global $user;

  // Garbage collection necessary when enforcing a minimum cache lifetime
  $cache_flush = variable_get('cache_flush_'. $table, 0);
  if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= time())) {
    // Reset the variable immediately to prevent a meltdown in heavy load situations.
    variable_set('cache_flush_'. $table, 0);
    // Time to flush old cache data
    db_query("DELETE FROM {". $table ."} WHERE expire != %d AND expire <= %d", CACHE_PERMANENT, $cache_flush);
  }

  $cache = db_fetch_object(db_query("SELECT data, created, headers, expire, serialized FROM {". $table ."} WHERE cid = '%s'", $cid));
  if (isset($cache->data)) {
    // If the data is permanent or we're not enforcing a minimum cache lifetime
    // always return the cached data.
    if ($cache->expire == CACHE_PERMANENT || !variable_get('cache_lifetime', 0)) {
      $cache->data = db_decode_blob($cache->data);
      if ($cache->serialized) {
        $cache->data = unserialize($cache->data);
      }
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
        if ($cache->serialized) {
          $cache->data = unserialize($cache->data);
        }
      }
    }
    return $cache;
  }
  return 0;
}

/**
 * Store data in the persistent cache.
 *
 * The persistent cache is split up into four database
 * tables. Contributed modules can add additional tables.
 *
 * 'cache_page': This table stores generated pages for anonymous
 * users. This is the only table affected by the page cache setting on
 * the administrator panel.
 *
 * 'cache_menu': Stores the cachable part of the users' menus.
 *
 * 'cache_filter': Stores filtered pieces of content. This table is
 * periodically cleared of stale entries by cron.
 *
 * 'cache': Generic cache storage table.
 *
 * The reasons for having several tables are as follows:
 *
 * - smaller tables allow for faster selects and inserts
 * - we try to put fast changing cache items and rather static
 *   ones into different tables. The effect is that only the fast
 *   changing tables will need a lot of writes to disk. The more
 *   static tables will also be better cachable with MySQL's query cache
 *
 * @param $cid
 *   The cache ID of the data to store.
 * @param $data
 *   The data to store in the cache. Complex data types will be automatically serialized before insertion.
 *   Strings will be stored as plain text and not serialized.
 * @param $table
 *   The table $table to store the data in. Valid core values are 'cache_filter',
 *   'cache_menu', 'cache_page', or 'cache'.
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
function cache_set($cid, $data, $table = 'cache', $expire = CACHE_PERMANENT, $headers = NULL) {
  $serialized = 0;
  if (is_object($data) || is_array($data)) {
    $data = serialize($data);
    $serialized = 1;
  }
  $created = time();
  db_query("UPDATE {". $table ."} SET data = %b, created = %d, expire = %d, headers = '%s', serialized = %d WHERE cid = '%s'", $data, $created, $expire, $headers, $serialized, $cid);
  if (!db_affected_rows()) {
    @db_query("INSERT INTO {". $table ."} (cid, data, created, expire, headers, serialized) VALUES ('%s', %b, %d, %d, '%s', %d)", $cid, $data, $created, $expire, $headers, $serialized);
  }
}

/**
 *
 * Expire data from the cache. If called without arguments, expirable
 * entries will be cleared from the cache_page and cache_block tables.
 *
 * @param $cid
 *   If set, the cache ID to delete. Otherwise, all cache entries that can
 *   expire are deleted.
 *
 * @param $table
 *   If set, the table $table to delete from. Mandatory
 *   argument if $cid is set.
 *
 * @param $wildcard
 *   If $wildcard is TRUE, cache IDs starting with $cid are deleted in
 *   addition to the exact cache ID specified by $cid.  If $wildcard is
 *   TRUE and $cid is '*' then the entire table $table is emptied.
 */
function cache_clear_all($cid = NULL, $table = NULL, $wildcard = FALSE) {
  global $user;

  if (!isset($cid) && !isset($table)) {
    // Clear the block cache first, so stale data will
    // not end up in the page cache.
    cache_clear_all(NULL, 'cache_block');
    cache_clear_all(NULL, 'cache_page');
    return;
  }

  if (empty($cid)) {
    if (variable_get('cache_lifetime', 0)) {
      // We store the time in the current user's $user->cache variable which
      // will be saved into the sessions table by sess_write(). We then
      // simulate that the cache was flushed for this user by not returning
      // cached data that was cached before the timestamp.
      $user->cache = time();

      $cache_flush = variable_get('cache_flush_'. $table, 0);
      if ($cache_flush == 0) {
        // This is the first request to clear the cache, start a timer.
        variable_set('cache_flush_'. $table, time());
      }
      else if (time() > ($cache_flush + variable_get('cache_lifetime', 0))) {
        // Clear the cache for everyone, cache_lifetime seconds have
        // passed since the first request to clear the cache.
        db_query("DELETE FROM {". $table ."} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
        variable_set('cache_flush_'. $table, 0);
      }
    }
    else {
      // No minimum cache lifetime, flush all temporary cache entries now.
      db_query("DELETE FROM {". $table ."} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
    }
  }
  else {
    if ($wildcard) {
      if ($cid == '*') {
        db_query("TRUNCATE TABLE {". $table ."}");
      }
      else {
        db_query("DELETE FROM {". $table ."} WHERE cid LIKE '%s%%'", $cid);
      }
    }
    else {
      db_query("DELETE FROM {". $table ."} WHERE cid = '%s'", $cid);
    }
  }
}

