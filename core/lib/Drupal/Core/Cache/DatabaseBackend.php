<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\DatabaseBackend.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Database;
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
   * Constructs a DatabaseBackend object.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct($bin) {
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
  public function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids) {
    try {
      // When serving cached pages, the overhead of using ::select() was found
      // to add around 30% overhead to the request. Since $this->bin is a
      // variable, this means the call to ::query() here uses a concatenated
      // string. This is highly discouraged under any other circumstances, and
      // is used here only due to the performance overhead we would incur
      // otherwise. When serving an uncached page, the overhead of using
      // ::select() is a much smaller proportion of the request.
      $result = Database::getConnection()->query('SELECT cid, data, created, expire, serialized, tags, checksum FROM {' . Database::getConnection()->escapeTable($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids));
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

    // Unserialize and return the cached data.
    if ($cache->serialized) {
      $cache->data = unserialize($cache->data);
    }

    return $cache;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
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
      Database::getConnection()->merge($this->bin)
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
  public function delete($cid) {
    Database::getConnection()->delete($this->bin)
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {
    // Delete in chunks when a large array is passed.
    do {
      Database::getConnection()->delete($this->bin)
        ->condition('cid', array_splice($cids, 0, 1000), 'IN')
        ->execute();
    }
    while (count($cids));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::flush().
   */
  public function flush() {
    Database::getConnection()->truncate($this->bin)->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::expire().
   */
  public function expire() {
    Database::getConnection()->delete($this->bin)
      ->condition('expire', CacheBackendInterface::CACHE_PERMANENT, '<>')
      ->condition('expire', REQUEST_TIME, '<')
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  public function garbageCollection() {
    $this->expire();
  }

  /**
   * Compares two checksums of tags. Used to determine whether to serve a cached
   * item or treat it as invalidated.
   *
   * @param integer $checksum
   *   The initial checksum to compare against.
   * @param array $tags
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
   * @return array
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
      Database::getConnection()->merge('cache_tags')
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
      try {
        if ($db_tags = Database::getConnection()->query('SELECT tag, invalidations FROM {cache_tags} WHERE tag IN (:tags)', array(':tags' => $query_tags))->fetchAllKeyed()) {
          self::$tagCache = array_merge(self::$tagCache, $db_tags);
          $checksum += array_sum($db_tags);
        }
      }
      catch (Exception $e) {
        // The database may not be available, so we'll ignore cache_set requests.
      }
    }
    return $checksum;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  public function isEmpty() {
    $this->garbageCollection();
    $query = Database::getConnection()->select($this->bin);
    $query->addExpression('1');
    $result = $query->range(0, 1)
      ->execute()
      ->fetchField();
    return empty($result);
  }
}
