<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\DatabaseBackend.
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
   * A static cache of all tags checked during the request.
   */
  protected static $tagCache = array();

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::__construct().
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
   */
  function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
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
      $result = db_query('SELECT cid, data, created, expire, serialized, tags, checksum FROM {' . db_escape_table($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids));
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

    // The cache data is invalid if any of its tags have been cleared since.
    if ($cache->tags) {
      $cache->tags = explode(' ', $cache->tags);
      if (!$this->validTags($cache->checksum, $cache->tags)) {
        return FALSE;
      }
    }

    // If the data is permanent or not subject to a minimum cache lifetime,
    // unserialize and return the cached data.
    if ($cache->serialized) {
      $cache->data = unserialize($cache->data);
    }

    return $cache;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  function set($cid, $data, $expire = CACHE_PERMANENT, array $tags = array()) {
    $fields = array(
      'serialized' => 0,
      'created' => REQUEST_TIME,
      'expire' => $expire,
      'tags' => implode(' ', $this->flattenTags($tags)),
      'checksum' => $this->checksumTags($tags),
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  function delete($cid) {
    db_delete($this->bin)
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  function deleteMultiple(array $cids) {
    // Delete in chunks when a large array is passed.
    do {
      db_delete($this->bin)
        ->condition('cid', array_splice($cids, 0, 1000), 'IN')
        ->execute();
    }
    while (count($cids));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deletePrefix().
   */
  function deletePrefix($prefix) {
    db_delete($this->bin)
      ->condition('cid', db_like($prefix) . '%', 'LIKE')
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::flush().
   */
  function flush() {
    db_truncate($this->bin)->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::expire().
   */
  function expire() {
    db_delete($this->bin)
      ->condition('expire', CACHE_PERMANENT, '<>')
      ->condition('expire', REQUEST_TIME, '<')
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  function garbageCollection() {
    $this->expire();
  }

  /**
   * Compares two checksums of tags. Used to determine whether to serve a cached
   * item or treat it as invalidated.
   *
   * @param integer @checksum
   *   The initial checksum to compare against.
   * @param array @tags
   *   An array of tags to calculate a checksum for.
   *
   * @return boolean
   *   TRUE if the checksums match, FALSE otherwise.
   */
  protected function validTags($checksum, array $tags) {
    return $checksum == $this->checksumTags($tags);
  }

  /**
   * Flattens a tags array into a numeric array suitable for string storage.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return
   *   Numeric array of flattened tag identifiers.
   */
  protected function flattenTags(array $tags) {
    if (isset($tags[0])) {
      return $tags;
    }

    $flat_tags = array();
    foreach ($tags as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $flat_tags[] = "$namespace:$value";
        }
      }
      else {
        $flat_tags[] = "$namespace:$values";
      }
    }
    return $flat_tags;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    foreach ($this->flattenTags($tags) as $tag) {
      unset(self::$tagCache[$tag]);
      db_merge('cache_tags')
        ->key(array('tag' => $tag))
        ->fields(array('invalidations' => 1))
        ->expression('invalidations', 'invalidations + 1')
        ->execute();
    }
  }

  /**
   * Returns the sum total of validations for a given set of tags.
   *
   * @param array $tags
   *   Associative array of tags.
   *
   * @return integer
   *   Sum of all invalidations.
   */
  protected function checksumTags($tags) {
    $checksum = 0;
    $query_tags = array();

    foreach ($this->flattenTags($tags) as $tag) {
      if (isset(self::$tagCache[$tag])) {
        $checksum += self::$tagCache[$tag];
      }
      else {
        $query_tags[] = $tag;
      }
   }
    if ($query_tags) {
      if ($db_tags = db_query('SELECT tag, invalidations FROM {cache_tags} WHERE tag IN (:tags)', array(':tags' => $query_tags))->fetchAllKeyed()) {
        self::$tagCache = array_merge(self::$tagCache, $db_tags);
        $checksum += array_sum($db_tags);
      }
    }
    return $checksum;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
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
