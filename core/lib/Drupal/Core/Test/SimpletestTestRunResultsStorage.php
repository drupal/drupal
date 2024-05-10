<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\ConnectionNotDefinedException;

/**
 * Implements a test run results storage compatible with legacy Simpletest.
 *
 * @internal
 */
class SimpletestTestRunResultsStorage implements TestRunResultsStorageInterface {

  /**
   * SimpletestTestRunResultsStorage constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use for inserting assertions.
   */
  public function __construct(
    protected Connection $connection,
  ) {
  }

  /**
   * Returns the database connection to use for inserting assertions.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to use for inserting assertions.
   */
  public static function getConnection(): Connection {
    // Check whether there is a test runner connection.
    // @see run-tests.sh
    // @todo Convert Simpletest UI runner to create + use this connection, too.
    try {
      $connection = Database::getConnection('default', 'test-runner');
    }
    catch (ConnectionNotDefinedException $e) {
      // Check whether there is a backup of the original default connection.
      // @see FunctionalTestSetupTrait::prepareEnvironment()
      try {
        $connection = Database::getConnection('default', 'simpletest_original_default');
      }
      catch (ConnectionNotDefinedException $e) {
        // If FunctionalTestSetupTrait::prepareEnvironment() failed, the
        // test-specific database connection does not exist yet/anymore, so
        // fall back to the default of the (UI) test runner.
        $connection = Database::getConnection('default', 'default');
      }
    }
    return $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function createNew(): int|string {
    return $this->connection->insert('simpletest_test_id')
      ->useDefaults(['test_id'])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function setDatabasePrefix(TestRun $test_run, string $database_prefix): void {
    $affected_rows = $this->connection->update('simpletest_test_id')
      ->fields(['last_prefix' => $database_prefix])
      ->condition('test_id', $test_run->id())
      ->execute();
    if (!$affected_rows) {
      throw new \RuntimeException('Failed to set up database prefix.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function insertLogEntry(TestRun $test_run, array $entry): bool {
    $entry['test_id'] = $test_run->id();
    $entry = array_merge([
      'function' => 'Unknown',
      'line' => 0,
      'file' => 'Unknown',
    ], $entry);

    return (bool) $this->connection->insert('simpletest')
      ->fields($entry)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function removeResults(TestRun $test_run): int {
    $this->connection->startTransaction('delete_test_run');
    $this->connection->delete('simpletest')
      ->condition('test_id', $test_run->id())
      ->execute();
    $count = $this->connection->delete('simpletest_test_id')
      ->condition('test_id', $test_run->id())
      ->execute();
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntriesByTestClass(TestRun $test_run): array {
    return $this->connection->select('simpletest')
      ->fields('simpletest')
      ->condition('test_id', $test_run->id())
      ->orderBy('test_class')
      ->orderBy('message_id')
      ->execute()
      ->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTestRunState(TestRun $test_run): array {
    // Define a subquery to identify the latest 'message_id' given the
    // $test_id.
    $max_message_id_subquery = $this->connection
      ->select('simpletest', 'sub')
      ->condition('test_id', $test_run->id());
    $max_message_id_subquery->addExpression('MAX([message_id])', 'max_message_id');

    // Run a select query to return 'last_prefix' from {simpletest_test_id} and
    // 'test_class' from {simpletest}.
    $select = $this->connection->select($max_message_id_subquery, 'st_sub');
    $select->join('simpletest', 'st', '[st].[message_id] = [st_sub].[max_message_id]');
    $select->join('simpletest_test_id', 'sttid', '[st].[test_id] = [sttid].[test_id]');
    $select->addField('sttid', 'last_prefix', 'db_prefix');
    $select->addField('st', 'test_class');

    return $select->execute()->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function buildTestingResultsEnvironment(bool $keep_results): void {
    $schema = $this->connection->schema();
    foreach (static::testingResultsSchema() as $name => $table_spec) {
      $table_exists = $schema->tableExists($name);
      if (!$keep_results && $table_exists) {
        $this->connection->truncate($name)->execute();
      }
      if (!$table_exists) {
        $schema->createTable($name, $table_spec);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateTestingResultsEnvironment(): bool {
    $schema = $this->connection->schema();
    return $schema->tableExists('simpletest') && $schema->tableExists('simpletest_test_id');
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp(): int {
    // Clear test results.
    $this->connection->startTransaction('delete_simpletest');
    $this->connection->delete('simpletest')->execute();
    $count = $this->connection->delete('simpletest_test_id')->execute();
    return $count;
  }

  /**
   * Defines the database schema for run-tests.sh and simpletest module.
   *
   * @return array
   *   Array suitable for use in a hook_schema() implementation.
   */
  public static function testingResultsSchema(): array {
    $schema['simpletest'] = [
      'description' => 'Stores simpletest messages',
      'fields' => [
        'message_id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique simpletest message ID.',
        ],
        'test_id' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Test ID, messages belonging to the same ID are reported together',
        ],
        'test_class' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The name of the class that created this message.',
        ],
        'status' => [
          'type' => 'varchar',
          'length' => 9,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Message status. Core understands pass, fail, exception.',
        ],
        'message' => [
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'The message itself.',
        ],
        'message_group' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The message group this message belongs to. For example: warning, browser, user.',
        ],
        'function' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Name of the assertion function or method that created this message.',
        ],
        'line' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Line number on which the function is called.',
        ],
        'file' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Name of the file where the function is called.',
        ],
      ],
      'primary key' => ['message_id'],
      'indexes' => [
        'reporter' => ['test_class', 'message_id'],
      ],
    ];
    $schema['simpletest_test_id'] = [
      'description' => 'Stores simpletest test IDs, used to auto-increment the test ID so that a fresh test ID is used.',
      'fields' => [
        'test_id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique simpletest ID used to group test results together. Each time a set of tests are run a new test ID is used.',
        ],
        'last_prefix' => [
          'type' => 'varchar',
          'length' => 60,
          'not null' => FALSE,
          'default' => '',
          'description' => 'The last database prefix used during testing.',
        ],
      ],
      'primary key' => ['test_id'],
    ];
    return $schema;
  }

}
