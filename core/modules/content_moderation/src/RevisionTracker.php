<?php

namespace Drupal\content_moderation;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Tracks metadata about revisions across entities.
 */
class RevisionTracker implements RevisionTrackerInterface {

  /**
   * The name of the SQL table we use for tracking.
   *
   * @var string
   */
  protected $tableName;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new RevisionTracker.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $table
   *   The table that should be used for tracking.
   */
  public function __construct(Connection $connection, $table = 'content_revision_tracker') {
    $this->connection = $connection;
    $this->tableName = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function setLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id) {
    try {
      $this->recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id);
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->ensureTableExists();
      $this->recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id);
    }

    return $this;
  }

  /**
   * Records the latest revision of a given entity.
   *
   * @param string $entity_type_id
   *   The machine name of the type of entity.
   * @param string $entity_id
   *   The Entity ID in question.
   * @param string $langcode
   *   The langcode of the revision we're saving. Each language has its own
   *   effective tree of entity revisions, so in different languages
   *   different revisions will be "latest".
   * @param int $revision_id
   *   The revision ID that is now the latest revision.
   *
   * @return int
   *   One of the valid returns from a merge query's execute method.
   */
  protected function recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id) {
    return $this->connection->merge($this->tableName)
      ->keys([
        'entity_type' => $entity_type_id,
        'entity_id' => $entity_id,
        'langcode' => $langcode,
      ])
      ->fields([
        'revision_id' => $revision_id,
      ])
      ->execute();
  }

  /**
   * Checks if the table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   */
  protected function ensureTableExists() {
    try {
      if (!$this->connection->schema()->tableExists($this->tableName)) {
        $this->connection->schema()->createTable($this->tableName, $this->schemaDefinition());
        return TRUE;
      }
    }
    catch (SchemaObjectExistsException $e) {
      // If another process has already created the table, attempting to
      // recreate it will throw an exception. In this case just catch the
      // exception and do nothing.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Defines the schema for the tracker table.
   *
   * @return array
   *   The schema API definition for the SQL storage table.
   */
  protected function schemaDefinition() {
    $schema = [
      'description' => 'Tracks the latest revision for any entity',
      'fields' => [
        'entity_type' => [
          'description' => 'The entity type',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'entity_id' => [
          'description' => 'The entity ID',
          'type' => 'int',
          'length' => 255,
          'not null' => TRUE,
          'default' => 0,
        ],
        'langcode' => [
          'description' => 'The language of the entity revision',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ],
        'revision_id' => [
          'description' => 'The latest revision ID for this entity',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['entity_type', 'entity_id', 'langcode'],
    ];

    return $schema;
  }

}
