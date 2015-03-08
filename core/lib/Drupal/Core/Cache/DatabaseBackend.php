<?php

/**
 * @file
 * Definition of Drupal\Core\Cache\DatabaseBackend.
 */

namespace Drupal\Core\Cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 *
 * @ingroup cache
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
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param string $bin
   *   The cache bin for which the object is created.
   */
  public function __construct(Connection $connection, CacheTagsChecksumInterface $checksum_provider, $bin) {
    // All cache tables should be prefixed with 'cache_'.
    $bin = 'cache_' . $bin;

    $this->bin = $bin;
    $this->connection = $connection;
    $this->checksumProvider = $checksum_provider;
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
    $cid_mapping = array();
    foreach ($cids as $cid) {
      $cid_mapping[$this->normalizeCid($cid)] = $cid;
    }
    // When serving cached pages, the overhead of using ::select() was found
    // to add around 30% overhead to the request. Since $this->bin is a
    // variable, this means the call to ::query() here uses a concatenated
    // string. This is highly discouraged under any other circumstances, and
    // is used here only due to the performance overhead we would incur
    // otherwise. When serving an uncached page, the overhead of using
    // ::select() is a much smaller proportion of the request.
    $result = array();
    try {
      $result = $this->connection->query('SELECT cid, data, created, expire, serialized, tags, checksum FROM {' . $this->connection->escapeTable($this->bin) . '} WHERE cid IN ( :cids[] ) ORDER BY cid', array(':cids[]' => array_keys($cid_mapping)));
    }
    catch (\Exception $e) {
      // Nothing to do.
    }
    $cache = array();
    foreach ($result as $item) {
      // Map the cache ID back to the original.
      $item->cid = $cid_mapping[$item->cid];
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

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the items's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
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
    Cache::validateTags($tags);
    $tags = array_unique($tags);
    // Sort the cache tags so that they are stored consistently in the database.
    sort($tags);
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
    $fields = array(
      'created' => round(microtime(TRUE), 3),
      'expire' => $expire,
      'tags' => implode(' ', $tags),
      'checksum' => $this->checksumProvider->getCurrentChecksum($tags),
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
      ->key('cid', $this->normalizeCid($cid))
      ->fields($fields)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    // Use a transaction so that the database can write the changes in a single
    // commit.
    $transaction = $this->connection->startTransaction();

    try {
      // Delete all items first so we can do one insert. Rather than multiple
      // merge queries.
      $this->deleteMultiple(array_keys($items));

      $query = $this->connection
        ->insert($this->bin)
        ->fields(array('cid', 'data', 'expire', 'created', 'serialized', 'tags', 'checksum'));

      foreach ($items as $cid => $item) {
        $item += array(
          'expire' => CacheBackendInterface::CACHE_PERMANENT,
          'tags' => array(),
        );

        Cache::validateTags($item['tags']);
        $item['tags'] = array_unique($item['tags']);
        // Sort the cache tags so that they are stored consistently in the DB.
        sort($item['tags']);

        $fields = array(
          'cid' => $cid,
          'expire' => $item['expire'],
          'created' => round(microtime(TRUE), 3),
          'tags' => implode(' ', $item['tags']),
          'checksum' => $this->checksumProvider->getCurrentChecksum($item['tags']),
        );

        if (!is_string($item['data'])) {
          $fields['data'] = serialize($item['data']);
          $fields['serialized'] = 1;
        }
        else {
          $fields['data'] = $item['data'];
          $fields['serialized'] = 0;
        }

        $query->values($fields);
      }

      $query->execute();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      // @todo Log something here or just re throw?
      throw $e;
    }
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
    $cids = array_values(array_map(array($this, 'normalizeCid'), $cids));
    try {
      // Delete in chunks when a large array is passed.
      foreach (array_chunk($cids, 1000) as $cids_chunk) {
        $this->connection->delete($this->bin)
          ->condition('cid', $cids_chunk, 'IN')
          ->execute();
      }
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where a cache table is cleared before it is set
      // with {cache_render} and {cache_data}.
      if (!$this->ensureBinExists()) {
        $this->catchException($e);
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
      // with {cache_render} and {cache_data}.
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
   * Implements Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function invalidateMultiple(array $cids) {
    $cids = array_values(array_map(array($this, 'normalizeCid'), $cids));
    try {
      // Update in chunks when a large array is passed.
      foreach (array_chunk($cids, 1000) as $cids_chunk) {
        $this->connection->update($this->bin)
          ->fields(array('expire' => REQUEST_TIME - 1))
          ->condition('cid', $cids_chunk, 'IN')
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->catchException($e);
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
        $database_schema->createTable($this->bin, $schema_definition);
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
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the cache is stale and the exception needs to
   * propagate.
   *
   * @param $e
   *   The exception.
   * @param string|null $table_name
   *   The table name. Defaults to $this->bin.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e, $table_name = NULL) {
    if ($this->connection->schema()->tableExists($table_name ?: $this->bin)) {
      throw $e;
    }
  }

  /**
   * Ensures that cache IDs have a maximum length of 255 characters.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   A cache ID that is at most 255 characters long.
   */
  protected function normalizeCid($cid) {
    // Nothing to do if the ID length is 255 characters or less.
    if (strlen($cid) <= 255) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * Defines the schema for the {cache_*} bin tables.
   */
  public function schemaDefinition() {
    $schema = array(
      'description' => 'Storage for the cache API.',
      'fields' => array(
        'cid' => array(
          'description' => 'Primary Key: Unique cache ID.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
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
          'description' => 'A timestamp with millisecond precision indicating when the cache entry was created.',
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
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
        'checksum' => array(
          'description' => 'The tag invalidation checksum when this entry was saved.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
      ),
      'indexes' => array(
        'expire' => array('expire'),
      ),
      'primary key' => array('cid'),
    );
    return $schema;
  }
}
