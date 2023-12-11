<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests update of timestamp fields to bigint.
 *
 * @group system
 */
class Y2038TimestampUpdateTest extends UpdatePathTestBase {

  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['forum'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The tables and column name of the time fields.
   *
   * The key is the table name and the values are the names of the time fields.
   *
   * @var string[][]
   */
  protected $tables = [
    'comment_entity_statistics' => ['last_comment_timestamp'],
    'forum_index' => ['created', 'last_comment_timestamp'],
    'history' => ['timestamp'],
    'locale_file' => ['timestamp', 'last_checked'],
    'node_counter' => ['timestamp'],
    'sessions' => ['timestamp'],
    'tracker_node' => ['changed'],
    'tracker_user' => ['changed'],
    'watchdog' => ['timestamp'],
    'batch' => ['timestamp'],
    'queue' => ['created', 'expire'],
    'flood' => ['expiration', 'timestamp'],
  ];

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the test database.
   *
   * @var string
   */
  protected $databaseName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Database\Connection $connection */
    $this->connection = \Drupal::service('database');
    if ($this->connection->databaseType() == 'pgsql') {
      $this->databaseName = 'public';
    }
    else {
      $this->databaseName = $this->connection->getConnectionOptions()['database'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      // Start with a standard install of Drupal 9.3.0 with the following
      // enabled modules: forum, language, locale, statistics and tracker.
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/Y2038-timestamp.php',
    ];
  }

  /**
   * Tests update of time fields.
   */
  public function testUpdate() {
    if (\Drupal::service('database')->databaseType() == 'sqlite') {
      $this->markTestSkipped("This test does not support the SQLite database driver.");
    }

    // Create a table starting with cache that is not a cache bin.
    \Drupal::service('database')->schema()->createTable('cache_bogus', [
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ]);

    $this->collectTimestampFieldsFromDatabase();
    // PostgreSQL returns the value 'integer' instead of 'int' when queried
    // about the column type. Some PostgreSQL tables are already of the type
    // 'bigint'.
    $this->assertTimestampFields(['int', 'integer', 'bigint']);

    $this->runUpdates();

    $this->assertTimestampFields(['bigint']);
  }

  /**
   * Collect the timestamp fields from the database and update table list.
   */
  public function collectTimestampFieldsFromDatabase() {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');

    // Build list of all tables and fields to check.
    $tables = $connection->schema()->findTables('migrate_map_%');
    foreach ($tables as $table) {
      $this->tables[$table] = ['last_imported'];
    }
    $tables = $connection->schema()->findTables('cache_%');
    $tables = array_filter($tables, function ($table) {
      return str_starts_with($table, 'cache_') && $table !== 'cache_bogus';
    });
    $this->assertNotEmpty($tables);
    foreach ($tables as $table) {
      $this->tables[$table] = ['expire'];
    }
  }

  /**
   * Asserts the size of the timestamp fields.
   */
  public function assertTimestampFields($expected_values) {
    // Check the size of all the fields.
    foreach ($this->tables as $table => $column_names) {
      $table_name = $this->connection->getPrefix() . $table;
      foreach ($column_names as $column_name) {
        $result = $this->connection->query("SELECT data_type FROM information_schema.columns WHERE table_schema = '$this->databaseName' and table_name = '$table_name' and column_name = '$column_name';")
          ->fetchField();

        $this->assertContains($result, $expected_values, "Failed for '$table_name' column '$column_name'");
      }
    }
  }

}
