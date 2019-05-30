<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;

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
   * The default maximum number of rows that this cache bin table can store.
   *
   * This maximum is introduced to ensure that the database is not filled with
   * hundred of thousand of cache entries with gigabytes in size.
   *
   * Read about how to change it in the @link cache Cache API topic. @endlink
   */
  const DEFAULT_MAX_ROWS = 5000;

  /**
   * -1 means infinite allows numbers of rows for the cache backend.
   */
  const MAXIMUM_NONE = -1;

  /**
   * The maximum number of rows that this cache bin table is allowed to store.
   *
   * @see ::MAXIMUM_NONE
   *
   * @var int
   */
  protected $maxRows;

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
   * @param int $max_rows
   *   (optional) The maximum number of rows that are allowed in this cache bin
   *   table.
   */
  public function __construct(Connection $connection, CacheTagsChecksumInterface $checksum_provider, $bin, $max_rows = NULL) {
    // All cache tables should be prefixed with 'cache_'.
    $bin = 'cache_' . $bin;

    $this->bin = $bin;
    $this->connection = $connection;
    $this->checksumProvider = $checksum_provider;
    $this->maxRows = $max_rows === NULL ? static::DEFAULT_MAX_ROWS : $max_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = [$cid];
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $cid_mapping = [];
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
    $result = [];
    try {
      $result = $this->connection->query('SELECT cid, data, created, expire, serialized, tags, checksum FROM {' . $this->connection->escapeTable($this->bin) . '} WHERE cid IN ( :cids[] ) ORDER BY cid', [':cids[]' => array_keys($cid_mapping)]);
    }
    catch (\Exception $e) {
      // Nothing to do.
    }
    $cache = [];
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
   *   An item loaded from self::get() or self::getMultiple().
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

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : [];

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
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    $this->setMultiple([
      $cid => [
        'data' => $data,
        'expire' => $expire,
        'tags' => $tags,
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    $try_again = FALSE;
    try {
      // The bin might not yet exist.
      $this->doSetMultiple($items);
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
      $this->doSetMultiple($items);
    }
  }

  /**
   * Stores multiple items in the persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::setMultiple()
   */
  protected function doSetMultiple(array $items) {
    $values = [];

    foreach ($items as $cid => $item) {
      $item += [
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => [],
      ];

      assert(Inspector::assertAllStrings($item['tags']), 'Cache Tags must be strings.');
      $item['tags'] = array_unique($item['tags']);
      // Sort the cache tags so that they are stored consistently in the DB.
      sort($item['tags']);

      $fields = [
        'cid' => $this->normalizeCid($cid),
        'expire' => $item['expire'],
        'created' => round(microtime(TRUE), 3),
        'tags' => implode(' ', $item['tags']),
        'checksum' => $this->checksumProvider->getCurrentChecksum($item['tags']),
      ];

      if (!is_string($item['data'])) {
        $fields['data'] = serialize($item['data']);
        $fields['serialized'] = 1;
      }
      else {
        $fields['data'] = $item['data'];
        $fields['serialized'] = 0;
      }
      $values[] = $fields;
    }

    // Use an upsert query which is atomic and optimized for multiple-row
    // merges.
    $query = $this->connection
      ->upsert($this->bin)
      ->key('cid')
      ->fields(['cid', 'expire', 'created', 'tags', 'checksum', 'data', 'serialized']);
    foreach ($values as $fields) {
      // Only pass the values since the order of $fields matches the order of
      // the insert fields. This is a performance optimization to avoid
      // unnecessary loops within the method.
      $query->values(array_values($fields));
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->deleteMultiple([$cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $cids = array_values(array_map([$this, 'normalizeCid'], $cids));
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple([$cid]);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $cids = array_values(array_map([$this, 'normalizeCid'], $cids));
    try {
      // Update in chunks when a large array is passed.
      foreach (array_chunk($cids, 1000) as $cids_chunk) {
        $this->connection->update($this->bin)
          ->fields(['expire' => REQUEST_TIME - 1])
          ->condition('cid', $cids_chunk, 'IN')
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    try {
      $this->connection->update($this->bin)
        ->fields(['expire' => REQUEST_TIME - 1])
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    try {
      // Bounded size cache bin, using FIFO.
      if ($this->maxRows !== static::MAXIMUM_NONE) {
        $first_invalid_create_time = $this->connection->select($this->bin)
          ->fields($this->bin, ['created'])
          ->orderBy("{$this->bin}.created", 'DESC')
          ->range($this->maxRows, $this->maxRows + 1)
          ->execute()
          ->fetchField();

        if ($first_invalid_create_time) {
          $this->connection->delete($this->bin)
            ->condition('created', $first_invalid_create_time, '<=')
            ->execute();
        }
      }

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
    catch (DatabaseException $e) {
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
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is at most 255 characters long.
   */
  protected function normalizeCid($cid) {
    // Nothing to do if the ID is a US ASCII string of 255 characters or less.
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
    if (strlen($cid) <= 255 && $cid_is_ascii) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    if (!$cid_is_ascii) {
      return $hash;
    }
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * Defines the schema for the {cache_*} bin tables.
   *
   * @internal
   */
  public function schemaDefinition() {
    $schema = [
      'description' => 'Storage for the cache API.',
      'fields' => [
        'cid' => [
          'description' => 'Primary Key: Unique cache ID.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
        ],
        'data' => [
          'description' => 'A collection of data to cache.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ],
        'expire' => [
          'description' => 'A Unix timestamp indicating when the cache entry should expire, or ' . Cache::PERMANENT . ' for never.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'created' => [
          'description' => 'A timestamp with millisecond precision indicating when the cache entry was created.',
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
          'not null' => TRUE,
          'default' => 0,
        ],
        'serialized' => [
          'description' => 'A flag to indicate whether content is serialized (1) or not (0).',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
          'default' => 0,
        ],
        'tags' => [
          'description' => 'Space-separated list of cache tags for this entry.',
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'checksum' => [
          'description' => 'The tag invalidation checksum when this entry was saved.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
      ],
      'indexes' => [
        'expire' => ['expire'],
        'created' => ['created'],
      ],
      'primary key' => ['cid'],
    ];
    return $schema;
  }

  /**
   * The maximum number of rows that this cache bin table is allowed to store.
   *
   * @return int
   */
  public function getMaxRows() {
    return $this->maxRows;
  }

}
