<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\DatabaseBackend.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

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
    $result = array();
    try {
      $result = $this->connection->query('SELECT cid, data, created, expire, serialized, tags, checksum_invalidations, checksum_deletions FROM {' . $this->connection->escapeTable($this->bin) . '} WHERE cid IN (:cids)', array(':cids' => $cids));
    }
    catch (\Exception $e) {
      // Nothing to do.
    }
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
   * @param object $cache
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
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

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
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    $try_again = FALSE;
    try {
      // The bin might not yet exist.
      $this->doSet($cid, $data, $expire, $tags);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the bins.
      if (!$try_again = $this->ensureBinExists()) {
        // If the exception happened for other reason than the missing bin
        // table, propagate the exception.
        throw $e;
      }
    }
    // Now that the bin has been created, try again if necessary.
    if ($try_again) {
      $this->doSet($cid, $data, $expire, $tags);
    }
  }

  /**
   * Actually set the cache.
   */
  protected function doSet($cid, $data, $expire, $tags) {
    $flat_tags = $this->flattenTags($tags);
    $deleted_tags = &drupal_static('Drupal\Core\Cache\DatabaseBackend::deletedTags', array());
    $invalidated_tags = &drupal_static('Drupal\Core\Cache\DatabaseBackend::invalidatedTags', array());
    // Remove tags that were already deleted or invalidated during this request
    // from the static caches so that another deletion or invalidation can
    // occur.
    foreach ($flat_tags as $tag) {
      if (isset($deleted_tags[$tag])) {
        unset($deleted_tags[$tag]);
      }
      if (isset($invalidated_tags[$tag])) {
        unset($invalidated_tags[$tag]);
      }
    }
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
      ->key('cid', $cid)
      ->fields($fields)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function delete($cid) {
    $this->deleteMultiple(array($cid));
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function deleteMultiple(array $cids) {
    try {
      // Delete in chunks when a large array is passed.
      do {
        $this->connection->delete($this->bin)
          ->condition('cid', array_splice($cids, 0, 1000), 'IN')
          ->execute();
      }
      while (count($cids));
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where a cache table is cleared before it is set
      // with {cache_block} and {cache_menu}.
      if (!$this->ensureBinExists()) {
        $this->catchException($e);
      }
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteTags().
   */
  public function deleteTags(array $tags) {
    $tag_cache = &drupal_static('Drupal\Core\Cache\CacheBackendInterface::tagCache', array());
    $deleted_tags = &drupal_static('Drupal\Core\Cache\DatabaseBackend::deletedTags', array());
    foreach ($this->flattenTags($tags) as $tag) {
      // Only delete tags once per request unless they are written again.
      if (isset($deleted_tags[$tag])) {
        continue;
      }
      $deleted_tags[$tag] = TRUE;
      unset($tag_cache[$tag]);
      try {
        $this->connection->merge('cache_tags')
          ->insertFields(array('deletions' => 1))
          ->expression('deletions', 'deletions + 1')
          ->key('tag', $tag)
          ->execute();
      }
      catch (\Exception $e) {
        $this->catchException($e, 'cache_tags');
      }
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function deleteAll() {
    try {
      $this->connection->truncate($this->bin)->execute();
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where a cache table is cleared before it is set
      // with {cache_block} and {cache_menu}.
      if (!$this->ensureBinExists()) {
        $this->catchException($e);
      }
    }
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
    try {
      // Update in chunks when a large array is passed.
      do {
        $this->connection->update($this->bin)
          ->fields(array('expire' => REQUEST_TIME - 1))
          ->condition('cid', array_splice($cids, 0, 1000), 'IN')
          ->execute();
      }
      while (count($cids));
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function invalidateTags(array $tags) {
    try {
      $tag_cache = &drupal_static('Drupal\Core\Cache\CacheBackendInterface::tagCache', array());
      $invalidated_tags = &drupal_static('Drupal\Core\Cache\DatabaseBackend::invalidatedTags', array());
      foreach ($this->flattenTags($tags) as $tag) {
        // Only invalidate tags once per request unless they are written again.
        if (isset($invalidated_tags[$tag])) {
          continue;
        }
        $invalidated_tags[$tag] = TRUE;
        unset($tag_cache[$tag]);
        $this->connection->merge('cache_tags')
          ->insertFields(array('invalidations' => 1))
          ->expression('invalidations', 'invalidations + 1')
          ->key('tag', $tag)
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->catchException($e, 'cache_tags');
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function invalidateAll() {
    try {
      $this->connection->update($this->bin)
        ->fields(array('expire' => REQUEST_TIME - 1))
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::garbageCollection().
   */
  public function garbageCollection() {
    try {
      $this->connection->delete($this->bin)
        ->condition('expire', Cache::PERMANENT, '<>')
        ->condition('expire', REQUEST_TIME, '<')
        ->execute();
    }
    catch (\Exception $e) {
      // If the table does not exist, it surely does not have garbage in it.
      // If the table exists, the next garbage collection will clean up.
      // There is nothing to do.
    }
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
   * @see \Drupal\Core\Cache\DatabaseBackend::flattenTags()
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
    try {
      $result = $query->range(0, 1)
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
    return empty($result);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    try {
      $this->connection->schema()->dropTable($this->bin);
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Check if the cache bin exists and create it if not.
   */
  protected function ensureBinExists() {
    try {
      $database_schema = $this->connection->schema();
      if (!$database_schema->tableExists($this->bin)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable($this->bin, $schema_definition['bin']);
        // If the bin doesn't exist, the cache tags table may also not exist.
        if (!$database_schema->tableExists('cache_tags')) {
          $database_schema->createTable('cache_tags', $schema_definition['cache_tags']);
        }
        return TRUE;
      }
    }
    // If another process has already created the cache table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when cache might be stale.
   *
   * If the cache_tags table does not yet exist, that's fine but if the table
   * exists and yet the query failed, then the cache is stale and the
   * exception needs to propagate.
   *
   * @param $e
   *   The exception.
   * @param string|null $table_name
   *   The table name, defaults to $this->bin. Can be cache_tags.
   */
  protected function catchException(\Exception $e, $table_name = NULL) {
    if ($this->connection->schema()->tableExists($table_name ?: $this->bin)) {
      throw $e;
    }
  }

  /**
   * Defines the schema for the cache bin and cache_tags table.
   */
  public function schemaDefinition() {
    $schema['bin'] = array(
      'description' => 'Storage for the cache API.',
      'fields' => array(
        'cid' => array(
          'description' => 'Primary Key: Unique cache ID.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'data' => array(
          'description' => 'A collection of data to cache.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ),
        'expire' => array(
          'description' => 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'created' => array(
          'description' => 'A Unix timestamp indicating when the cache entry was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'serialized' => array(
          'description' => 'A flag to indicate whether content is serialized (1) or not (0).',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
          'default' => 0,
        ),
        'tags' => array(
          'description' => 'Space-separated list of cache tags for this entry.',
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'checksum_invalidations' => array(
          'description' => 'The tag invalidation sum when this entry was saved.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'checksum_deletions' => array(
          'description' => 'The tag deletion sum when this entry was saved.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'expire' => array('expire'),
      ),
      'primary key' => array('cid'),
    );
    $schema['cache_tags'] = array(
      'description' => 'Cache table for tracking cache tags related to the cache bin.',
      'fields' => array(
        'tag' => array(
          'description' => 'Namespace-prefixed tag string.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'invalidations' => array(
          'description' => 'Number incremented when the tag is invalidated.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'deletions' => array(
          'description' => 'Number incremented when the tag is deleted.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('tag'),
    );
    return $schema;
  }
}
