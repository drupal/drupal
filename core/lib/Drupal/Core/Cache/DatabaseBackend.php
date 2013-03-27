<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\DatabaseBackend.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct(Connection $connection, $bin) {
    // All cache tables should be prefixed with 'cache_', except for the
    // default 'cache' bin.
    if ($bin != 'cache') {
      $bin = 'cache_' . $bin;
    }
    $this->bin = $bin;
    $this->connection = $connection;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::get().
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // When serving cached pages, the overhead of using ::select() was found
    // to add around 30% overhead to the request. Since $this->bin is a
    // variable, this means the call to ::query() here uses a concatenated
    // string. This is highly discouraged under any other circumstances, and
    // is used here only due to the performance overhead we would incur
    // otherwise. When serving an uncached page, the overhead of using
    // ::select() is a much smaller proportion of the request.
    $result = $this->connection->query('SELECT cid, data, created, expire, serialized, tags, checksum_invalidations, checksum_deletions FROM {' . $this->connection->escapeTable($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids));
    $cache = array();
    foreach ($result as $item) {
      $item = $this->prepareItem($item, $allow_invalid);
      if ($item) {
        $cache[$item->cid] = $item;
      }
    }
    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   * @param bool $allow_invalid
   *   If FALSE, the method returns FALSE if the cache item is not valid.
   *
   * @return mixed|false
   *   The item with data unserialized as appropriate and a property indicating
   *   whether the item is valid, or FALSE if there is no valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : array();

    $checksum = $this->checksumTags($cache->tags);

    // Check if deleteTags() has been called with any of the entry's tags.
    if ($cache->checksum_deletions != $checksum['deletions']) {
      return FALSE;
    }

    // Check expire time.
    $cache->valid = $cache->expire == CacheBackendInterface::CACHE_PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the entry's tags.
    if ($cache->checksum_invalidations != $checksum['invalidations']) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
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
    $flat_tags = $this->flattenTags($tags);
    $checksum = $this->checksumTags($flat_tags);
    $fields = array(
      'serialized' => 0,
      'created' => REQUEST_TIME,
      'expire' => $expire,
      'tags' => implode(' ', $flat_tags),
      'checksum_invalidations' => $checksum['invalidations'],
      'checksum_deletions' => $checksum['deletions'],
    );
    if (!is_string($data)) {
      $fields['data'] = serialize($data);
      $fields['serialized'] = 1;
    }
    else {
      $fields['data'] = $data;
      $fields['serialized'] = 0;
    }

    $this->connection->merge($this->bin)
      ->key(array('cid' => $cid))
      ->fields($fields)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {
    $this->connection->delete($this->bin)
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {
    // Delete in chunks when a large array is passed.
    do {
      $this->connection->delete($this->bin)
        ->condition('cid', array_splice($cids, 0, 1000), 'IN')
        ->execute();
    }
    while (count($cids));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteTags().
   */
  public function deleteTags(array $tags) {
    $tag_cache = &drupal_static('Drupal\Core\Cache\CacheBackendInterface::tagCache');
    foreach ($this->flattenTags($tags) as $tag) {
      unset($tag_cache[$tag]);
      $this->connection->merge('cache_tags')
        ->insertFields(array('deletions' => 1))
        ->expression('deletions', 'deletions + 1')
        ->key(array('tag' => $tag))
        ->execute();
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function deleteAll() {
    $this->connection->truncate($this->bin)->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidate().
   */
  public function invalidate($cid) {
    $this->invalidateMultiple(array($cid));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalideMultiple().
   */
  public function invalidateMultiple(array $cids) {
    // Update in chunks when a large array is passed.
    do {
      $this->connection->update($this->bin)
        ->fields(array('expire' => REQUEST_TIME - 1))
        ->condition('cid', array_splice($cids, 0, 1000), 'IN')
        ->execute();
    }
    while (count($cids));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    $tag_cache = &drupal_static('Drupal\Core\Cache\CacheBackendInterface::tagCache');
    foreach ($this->flattenTags($tags) as $tag) {
      unset($tag_cache[$tag]);
      $this->connection->merge('cache_tags')
        ->insertFields(array('invalidations' => 1))
        ->expression('invalidations', 'invalidations + 1')
        ->key(array('tag' => $tag))
        ->execute();
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function invalidateAll() {
    $this->connection->update($this->bin)
      ->fields(array('expire' => REQUEST_TIME - 1))
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  public function garbageCollection() {
    Database::getConnection()->delete($this->bin)
      ->condition('expire', CacheBackendInterface::CACHE_PERMANENT, '<>')
      ->condition('expire', REQUEST_TIME, '<')
      ->execute();
  }

  /**
   * 'Flattens' a tags array into an array of strings.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return array
   *   An indexed array of flattened tag identifiers.
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
   * Returns the sum total of validations for a given set of tags.
   *
   * @param array $tags
   *   Array of flat tags.
   *
   * @return int
   *   Sum of all invalidations.
   *
   * @see Drupal\Core\Cache\DatabaseBackend::flattenTags()
   */
  protected function checksumTags($flat_tags) {
    $tag_cache = &drupal_static('Drupal\Core\Cache\CacheBackendInterface::tagCache', array());

    $checksum = array(
      'invalidations' => 0,
      'deletions' => 0,
    );

    $query_tags = array_diff($flat_tags, array_keys($tag_cache));
    if ($query_tags) {
      $db_tags = $this->connection->query('SELECT tag, invalidations, deletions FROM {cache_tags} WHERE tag IN (:tags)', array(':tags' => $query_tags))->fetchAllAssoc('tag', \PDO::FETCH_ASSOC);
      $tag_cache += $db_tags;

      // Fill static cache with empty objects for tags not found in the database.
      $tag_cache += array_fill_keys(array_diff($query_tags, array_keys($db_tags)), $checksum);
    }

    foreach ($flat_tags as $tag) {
      $checksum['invalidations'] += $tag_cache[$tag]['invalidations'];
      $checksum['deletions'] += $tag_cache[$tag]['deletions'];
    }

    return $checksum;
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  public function isEmpty() {
    $this->garbageCollection();
    $query = $this->connection->select($this->bin);
    $query->addExpression('1');
    $result = $query->range(0, 1)
      ->execute()
      ->fetchField();
    return empty($result);
  }
}
