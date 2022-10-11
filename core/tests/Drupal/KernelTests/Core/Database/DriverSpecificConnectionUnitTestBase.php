<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests management of database connections.
 */
abstract class DriverSpecificConnectionUnitTestBase extends DriverSpecificKernelTestBase {

  /**
   * A target connection identifier to be used for testing.
   */
  const TEST_TARGET_CONNECTION = 'DatabaseConnectionUnitTest';

  /**
   * A database connection used for monitoring processes.
   */
  protected $monitor;

  /**
   * The connection ID of the current test connection.
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an additional connection to monitor the connections being opened
    // and closed in this test.
    $connection_info = Database::getConnectionInfo();
    Database::addConnectionInfo('default', 'monitor', $connection_info['default']);
    $this->monitor = Database::getConnection('monitor');

    // Add a new target to the connection, by cloning the current connection.
    $connection_info = Database::getConnectionInfo();
    Database::addConnectionInfo('default', static::TEST_TARGET_CONNECTION, $connection_info['default']);

    // Verify that the new target exists.
    $info = Database::getConnectionInfo();

    // New connection info found.
    $this->assertSame($connection_info['default'], $info[static::TEST_TARGET_CONNECTION]);

    // Add and open a new connection.
    Database::getConnection(static::TEST_TARGET_CONNECTION);

    // Verify that there is a new connection.
    $this->id = $this->getConnectionId();
    $this->assertConnection($this->id);
  }

  /**
   * Returns a set of queries specific for the database in testing.
   */
  abstract protected function getQuery(): array;

  /**
   * Returns the connection ID of the current test connection.
   *
   * @return int
   */
  protected function getConnectionId(): int {
    return (int) Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['connection_id'])->fetchField();
  }

  /**
   * Asserts that a connection ID exists.
   *
   * @param int $id
   *   The connection ID to verify.
   *
   * @internal
   */
  protected function assertConnection(int $id): void {
    $this->assertArrayHasKey($id, $this->monitor->query($this->getQuery()['processlist'])->fetchAllKeyed(0, 0));
  }

  /**
   * Asserts that a connection ID does not exist.
   *
   * @param int $id
   *   The connection ID to verify.
   *
   * @internal
   */
  protected function assertNoConnection(int $id): void {
    $this->assertArrayNotHasKey($id, $this->monitor->query($this->getQuery()['processlist'])->fetchAllKeyed(0, 0));
  }

  /**
   * Tests Database::closeConnection() without query.
   *
   * @todo getConnectionId() executes a query.
   */
  public function testOpenClose(): void {
    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($this->id);
  }

  /**
   * Tests Database::closeConnection() with a query.
   */
  public function testOpenQueryClose(): void {
    // Execute a query.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['show_tables']);

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($this->id);
  }

  /**
   * Tests Database::closeConnection() with a query and custom prefetch method.
   */
  public function testOpenQueryPrefetchClose(): void {
    // Execute a query.
    Database::getConnection(static::TEST_TARGET_CONNECTION)->query($this->getQuery()['show_tables'])->fetchCol();

    // Close the connection.
    Database::closeConnection(static::TEST_TARGET_CONNECTION);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($this->id);
  }

  /**
   * Tests Database::closeConnection() with a select query.
   */
  public function testOpenSelectQueryClose(): void {
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
    $this->assertNoConnection($this->id);
  }

  /**
   * Tests pdo options override.
   */
  public function testConnectionOpen() {
    $reflection = new \ReflectionObject($this->connection);
    $connection_property = $reflection->getProperty('connection');
    $connection_property->setAccessible(TRUE);
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
