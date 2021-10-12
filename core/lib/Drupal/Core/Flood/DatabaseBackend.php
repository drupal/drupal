<?php

namespace Drupal\Core\Flood;

use Drupal\Core\Database\DatabaseException;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;

/**
 * Defines the database flood backend. This is the default Drupal backend.
 */
class DatabaseBackend implements FloodInterface {

  /**
   * The database table name.
   */
  const TABLE_NAME = 'flood';

  /**
   * The database connection used to store flood event information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the DatabaseBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the flood event
   *   information.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(Connection $connection, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function register($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    $try_again = FALSE;
    try {
      $this->doInsert($name, $window, $identifier);
    }
    catch (\Exception $e) {
      $try_again = $this->ensureTableExists();
      if (!$try_again) {
        throw $e;
      }
    }
    if ($try_again) {
      $this->doInsert($name, $window, $identifier);
    }
  }

  /**
   * Inserts an event into the flood table.
   *
   * @param string $name
   *   The name of an event.
   * @param int $window
   *   Number of seconds before this event expires.
   * @param string $identifier
   *   Unique identifier of the current user.
   *
   * @see \Drupal\Core\Flood\DatabaseBackend::register
   */
  protected function doInsert($name, $window, $identifier) {
    $this->connection->insert(static::TABLE_NAME)
      ->fields([
        'event' => $name,
        'identifier' => $identifier,
        'timestamp' => REQUEST_TIME,
        'expiration' => REQUEST_TIME + $window,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clear($name, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('event', $name)
        ->condition('identifier', $identifier)
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
    }
    try {
      $number = $this->connection->select(static::TABLE_NAME, 'f')
        ->condition('event', $name)
        ->condition('identifier', $identifier)
        ->condition('timestamp', REQUEST_TIME - $window, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
      return ($number < $threshold);
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    try {
      $return = $this->connection->delete(static::TABLE_NAME)
        ->condition('expiration', REQUEST_TIME, '<')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Check if the flood table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      $schema_definition = $this->schemaDefinition();
      $database_schema->createTable(static::TABLE_NAME, $schema_definition);
    }
    // If another process has already created the table, attempting to create
    // it will throw an exception. In this case just catch the exception and do
    // nothing.
    catch (DatabaseException $e) {
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Act on an exception when flood might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the flood is stale and the exception needs to
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
   * Defines the schema for the flood table.
   *
   * @internal
   */
  public function schemaDefinition() {
    return [
      'description' => 'Flood controls the threshold of events, such as the number of contact attempts.',
      'fields' => [
        'fid' => [
          'description' => 'Unique flood event ID.',
          'type' => 'serial',
          'not null' => TRUE,
        ],
        'event' => [
          'description' => 'Name of event (e.g. contact).',
          'type' => 'varchar_ascii',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
        ],
        'identifier' => [
          'description' => 'Identifier of the visitor, such as an IP address or hostname.',
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'timestamp' => [
          'description' => 'Timestamp of the event.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'expiration' => [
          'description' => 'Expiration timestamp. Expired events are purged on cron run.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['fid'],
      'indexes' => [
        'allow' => ['event', 'identifier', 'timestamp'],
        'purge' => ['expiration'],
      ],
    ];
  }

}
