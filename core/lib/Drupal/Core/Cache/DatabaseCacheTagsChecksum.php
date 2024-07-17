<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;

/**
 * Cache tags invalidations checksum implementation that uses the database.
 */
class DatabaseCacheTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  use CacheTagsChecksumTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
  protected function doInvalidateTags(array $tags) {
    try {
      foreach ($tags as $tag) {
        $this->connection->merge('cachetags')
          ->insertFields(['invalidations' => 1])
          ->expression('invalidations', '[invalidations] + 1')
          ->key('tag', $tag)
          ->execute();
      }
    }
    catch (\Exception $e) {
      // Create the cache table, which will be empty. This fixes cases during
      // core install where cache tags are invalidated before the table is
      // created.
      if (!$this->ensureTableExists()) {
        throw $e;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getTagInvalidationCounts(array $tags) {
    try {
      return $this->connection->query('SELECT [tag], [invalidations] FROM {cachetags} WHERE [tag] IN ( :tags[] )', [':tags[]' => $tags])
        ->fetchAllKeyed();
    }
    catch (\Exception $e) {
      // If the table does not exist yet, create.
      if (!$this->ensureTableExists()) {
        throw $e;
      }
    }
    return [];
  }

  /**
   * Check if the cache tags table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      $schema_definition = $this->schemaDefinition();
      $database_schema->createTable('cachetags', $schema_definition);
    }
    // If another process has already created the cachetags table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (DatabaseException) {
    }
    catch (\Exception) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Defines the schema for the {cachetags} table.
   *
   * @internal
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
   * {@inheritdoc}
   */
  public function getDatabaseConnection() {
    return $this->connection;
  }

}
