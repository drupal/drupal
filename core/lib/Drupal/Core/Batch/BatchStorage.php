<?php

namespace Drupal\Core\Batch;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Access\CsrfTokenGenerator;

class BatchStorage implements BatchStorageInterface {

  /**
   * The table name.
   */
  const TABLE_NAME = 'batch';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs the database batch storage service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(Connection $connection, SessionInterface $session, CsrfTokenGenerator $csrf_token) {
    $this->connection = $connection;
    $this->session = $session;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // Ensure that a session is started before using the CSRF token generator.
    $this->session->start();
    try {
      $batch = $this->connection->query("SELECT batch FROM {batch} WHERE bid = :bid AND token = :token", [
        ':bid' => $id,
        ':token' => $this->csrfToken->get($id),
      ])->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      $batch = FALSE;
    }
    if ($batch) {
      return unserialize($batch);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    try {
      $this->connection->delete('batch')
        ->condition('bid', $id)
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $batch) {
    try {
      $this->connection->update('batch')
        ->fields(['batch' => serialize($batch)])
        ->condition('bid', $batch['id'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup() {
    try {
      // Cleanup the batch table and the queue for failed batches.
      $this->connection->delete('batch')
        ->condition('timestamp', REQUEST_TIME - 864000, '<')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $batch) {
    // Ensure that a session is started before using the CSRF token generator.
    $this->session->start();
    $try_again = FALSE;
    try {
      // The batch table might not yet exist.
      $this->doCreate($batch);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing table,
        // propagate the exception.
        throw $e;
      }
    }
    // Now that the table has been created, try again if necessary.
    if ($try_again) {
      $this->doCreate($batch);
    }
  }

  /**
   * Saves a batch.
   *
   * @param array $batch
   *   The array representing the batch to create.
   */
  protected function doCreate(array $batch) {
    $this->connection->insert('batch')
      ->fields([
        'bid' => $batch['id'],
        'timestamp' => REQUEST_TIME,
        'token' => $this->csrfToken->get($batch['id']),
        'batch' => serialize($batch),
      ])
      ->execute();
  }

  /**
   * Check if the table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      if (!$database_schema->tableExists(static::TABLE_NAME)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable(static::TABLE_NAME, $schema_definition);
        return TRUE;
      }
    }
    // If another process has already created the batch table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (DatabaseException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when batch might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the batch is stale and the exception needs to
   * propagate.
   *
   * @param $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e) {
    if ($this->connection->schema()->tableExists(static::TABLE_NAME)) {
      throw $e;
    }
  }

  /**
   * Defines the schema for the batch table.
   *
   * @internal
   */
  public function schemaDefinition() {
    return [
      'description' => 'Stores details about batches (processes that run in multiple HTTP requests).',
      'fields' => [
        'bid' => [
          'description' => 'Primary Key: Unique batch ID.',
          // This is not a serial column, to allow both progressive and
          // non-progressive batches. See batch_process().
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'token' => [
          'description' => "A string token generated against the current user's session id and the batch id, used to ensure that only the user who submitted the batch can effectively access it.",
          'type' => 'varchar_ascii',
          'length' => 64,
          'not null' => TRUE,
        ],
        'timestamp' => [
          'description' => 'A Unix timestamp indicating when this batch was submitted for processing. Stale batches are purged at cron time.',
          'type' => 'int',
          'not null' => TRUE,
        ],
        'batch' => [
          'description' => 'A serialized array containing the processing data for the batch.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ],
      ],
      'primary key' => ['bid'],
      'indexes' => [
        'token' => ['token'],
      ],
    ];
  }

}
