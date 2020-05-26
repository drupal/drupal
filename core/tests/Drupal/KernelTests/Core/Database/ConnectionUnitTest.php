<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests management of database connections.
 *
 * @group Database
 */
class ConnectionUnitTest extends KernelTestBase {

  /**
   * A target connection identifier to be used for testing.
   */
  const TEST_TARGET_CONNECTION = 'DatabaseConnectionUnitTest';

  /**
   * The default database connection used for testing.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * A database connection used for monitoring processes.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $monitor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = Database::getConnection();

    // Create an additional connection to monitor the connections being opened
    // and closed in this test.
    $connection_info = Database::getConnectionInfo();
    Database::addConnectionInfo('default', 'monitor', $connection_info['default']);
    $this->monitor = Database::getConnection('monitor');
  }

  /**
   * Returns a set of queries specific for the database in testing.
   */
  protected function getQuery() {
    if ($this->connection->databaseType() == 'pgsql') {
      return [
        'connection_id' => 'SELECT pg_backend_pid()',
        'processlist' => 'SELECT pid FROM pg_stat_activity',
        'show_tables' => 'SELECT * FROM pg_catalog.pg_tables',
      ];
    }
    else {
      return [
        'connection_id' => 'SELECT CONNECTION_ID()',
        'processlist' => 'SHOW PROCESSLIST',
        'show_tables' => 'SHOW TABLES',
      ];
    }
  }

  /**
   * Adds a new database connection info to Database.
   */
  protected function addConnection() {
    // Add a new target to the connection, by cloning the current connection.
    $connection_info = Database::getConnectionInfo();
    Database::addConnectionInfo('default', static::TEST_TARGET_CONNECTION, $connection_info['default']);

    // Verify that the new target exists.
    $info = Database::getConnectionInfo();
    // New connection info found.
    $this->assertSame($connection_info['default'], $info[static::TEST_TARGET_CONNECTION]);
  }

  /**
   * Returns the connection ID of the current test connection.
   *
   * @return int
   */
  protected function getConnectionId() {
    return (int) Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['connection_id'])->fetchField();
  }

  /**
   * Asserts that a connection ID exists.
   *
   * @param int $id
   *   The connection ID to verify.
   */
  protected function assertConnection($id) {
    $list = $this->monitor->query($this->getQuery()['processlist'])->fetchAllKeyed(0, 0);
    return $this->assertTrue(isset($list[$id]), new FormattableMarkup('Connection ID @id found.', ['@id' => $id]));
  }

  /**
   * Asserts that a connection ID does not exist.
   *
   * @param int $id
   *   The connection ID to verify.
   */
  protected function assertNoConnection($id) {
    $list = $this->monitor->query($this->getQuery()['processlist'])->fetchAllKeyed(0, 0);
    return $this->assertFalse(isset($list[$id]), new FormattableMarkup('Connection ID @id not found.', ['@id' => $id]));
  }

  /**
   * Tests Database::closeConnection() without query.
   *
   * @todo getConnectionId() executes a query.
   */
  public function testOpenClose() {
    // Do not run this test for an SQLite database.
    if ($this->connection->databaseType() == 'sqlite') {
      $this->markTestSkipped("This tests can not run with an SQLite database.");
    }

    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionId();
    Database::getConnection(static::TEST_TARGET_CONNECTION);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a query.
   */
  public function testOpenQueryClose() {
    // Do not run this test for an SQLite database.
    if ($this->connection->databaseType() == 'sqlite') {
      $this->markTestSkipped("This tests can not run with an SQLite database.");
    }

    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionId();
    Database::getConnection(static::TEST_TARGET_CONNECTION);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Execute a query.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['show_tables']);

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a query and custom prefetch method.
   */
  public function testOpenQueryPrefetchClose() {
    // Do not run this test for an SQLite database.
    if ($this->connection->databaseType() == 'sqlite') {
      $this->markTestSkipped("This tests can not run with an SQLite database.");
    }

    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionId();
    Database::getConnection(static::TEST_TARGET_CONNECTION);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Execute a query.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['show_tables'])->fetchCol();

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a select query.
   */
  public function testOpenSelectQueryClose() {
    // Do not run this test for an SQLite database.
    if ($this->connection->databaseType() == 'sqlite') {
      $this->markTestSkipped("This tests can not run with an SQLite database.");
    }

    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionId();
    Database::getConnection(static::TEST_TARGET_CONNECTION);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Create a table.
    $name = 'foo';
    Database::getConnection(static::TEST_TARGET_CONNECTION)->schema()->createTable($name, [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ]);

    // Execute a query.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->select('foo', 'f')
      ->fields('f', ['name'])
      ->execute()
      ->fetchAll();

    // Drop the table.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->schema()->dropTable($name);

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests pdo options override.
   */
  public function testConnectionOpen() {
    $reflection = new \ReflectionObject($this->connection);
    $connection_property = $reflection->getProperty('connection');
    $connection_property->setAccessible(TRUE);
    // Skip this test when a database driver does not implement PDO.
    // An alternative database driver that does not implement PDO
    // should implement its own connection test.
    if (get_class($connection_property->getValue($this->connection)) !== 'PDO') {
      $this->markTestSkipped('Ignored PDO connection unit test for this driver because it does not implement PDO.');
    }
    $error_mode = $connection_property->getValue($this->connection)
      ->getAttribute(\PDO::ATTR_ERRMODE);
    // Ensure the default error mode is set to exception.
    $this->assertSame(\PDO::ERRMODE_EXCEPTION, $error_mode);

    $connection_info = Database::getConnectionInfo();
    $connection_info['default']['pdo'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_SILENT;
    Database::addConnectionInfo('test', 'default', $connection_info['default']);
    $test_connection = Database::getConnection('default', 'test');

    $reflection = new \ReflectionObject($test_connection);
    $connection_property = $reflection->getProperty('connection');
    $connection_property->setAccessible(TRUE);
    $error_mode = $connection_property->getValue($test_connection)
      ->getAttribute(\PDO::ATTR_ERRMODE);
    // Ensure PDO connection options can be overridden.
    $this->assertSame(\PDO::ERRMODE_SILENT, $error_mode);

    Database::removeConnection('test');
  }

}
