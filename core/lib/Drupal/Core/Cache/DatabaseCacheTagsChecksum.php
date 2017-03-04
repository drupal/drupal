<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Cache tags invalidations checksum implementation that uses the database.
 */
class DatabaseCacheTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Contains already loaded cache invalidations from the database.
   *
   * @var array
   */
  protected $tagCache = [];

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var array
   */
  protected $invalidatedTags = [];

  /**
   * Constructs a DatabaseCacheTagsChecksum object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    try {
      foreach ($tags as $tag) {
        // Only invalidate tags once per request unless they are written again.
        if (isset($this->invalidatedTags[$tag])) {
          continue;
        }
        $this->invalidatedTags[$tag] = TRUE;
        unset($this->tagCache[$tag]);
        $this->connection->merge('cachetags')
          ->insertFields(['invalidations' => 1])
          ->expression('invalidations', 'invalidations + 1')
          ->key('tag', $tag)
          ->execute();
      }
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where cache tags are invalidated before the table is
      // created.
      if (!$this->ensureTableExists()) {
        $this->catchException($e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    // Remove tags that were already invalidated during this request from the
    // static caches so that another invalidation can occur later in the same
    // request. Without that, written cache items would not be invalidated
    // correctly.
    foreach ($tags as $tag) {
      unset($this->invalidatedTags[$tag]);
    }
    return $this->calculateChecksum($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($checksum, array $tags) {
    return $checksum == $this->calculateChecksum($tags);
  }

  /**
   * Calculates the current checksum for a given set of tags.
   *
   * @param array $tags
   *   The array of tags to calculate the checksum for.
   *
   * @return int
   *   The calculated checksum.
   */
  protected function calculateChecksum(array $tags) {
    $checksum = 0;

    $query_tags = array_diff($tags, array_keys($this->tagCache));
    if ($query_tags) {
      $db_tags = [];
      try {
        $db_tags = $this->connection->query('SELECT tag, invalidations FROM {cachetags} WHERE tag IN ( :tags[] )', [':tags[]' => $query_tags])
          ->fetchAllKeyed();
        $this->tagCache += $db_tags;
      }
      catch (\Exception $e) {
        // If the table does not exist yet, create.
        if (!$this->ensureTableExists()) {
          $this->catchException($e);
        }
      }
      // Fill static cache with empty objects for tags not found in the database.
      $this->tagCache += array_fill_keys(array_diff($query_tags, array_keys($db_tags)), 0);
    }

    foreach ($tags as $tag) {
      $checksum += $this->tagCache[$tag];
    }

    return $checksum;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->tagCache = [];
    $this->invalidatedTags = [];
  }

  /**
   * Check if the cache tags table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      // Create the cache tags table if it does not exist.
      if (!$database_schema->tableExists('cachetags')) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable('cachetags', $schema_definition);

        return TRUE;
      }
    }
    // If another process has already created the cachetags table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Defines the schema for the {cachetags} table.
   */
  public function schemaDefinition() {
    $schema = [
      'description' => 'Cache table for tracking cache tag invalidations.',
      'fields' => [
        'tag' => [
          'description' => 'Namespace-prefixed tag string.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'invalidations' => [
          'description' => 'Number incremented when the tag is invalidated.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['tag'],
    ];
    return $schema;
  }

  /**
   * Act on an exception when cache might be stale.
   *
   * If the {cachetags} table does not yet exist, that's fine but if the table
   * exists and yet the query failed, then the cache is stale and the
   * exception needs to propagate.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e) {
    if ($this->connection->schema()->tableExists('cachetags')) {
      throw $e;
    }
  }

}
