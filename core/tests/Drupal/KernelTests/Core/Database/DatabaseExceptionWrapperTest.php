<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests exceptions thrown by queries.
 *
 * @group Database
 */
class DatabaseExceptionWrapperTest extends KernelTestBase {

  /**
   * Tests Connection::prepareStatement exceptions on execution.
   *
   * Core database drivers use PDO emulated statements or the StatementPrefetch
   * class, which defer the statement check to the moment of the execution.
   */
  public function testPrepareStatementFailOnExecution() {
    $connection = Database::getConnection();
    $connection_reflection_class = new \ReflectionClass($connection);
    $client_connection_property = $connection_reflection_class->getProperty('connection');
    $client_connection = $client_connection_property->getValue($connection);
    if (!$client_connection instanceof \PDO) {
      $this->markTestSkipped("This tests can only run for drivers wrapping \\PDO connections.");
    }
    $this->expectException(\PDOException::class);
    $stmt = $connection->prepareStatement('bananas', []);
    $stmt->execute();
  }

  /**
   * Tests Connection::prepareStatement exceptions on preparation.
   *
   * Core database drivers use PDO emulated statements or the StatementPrefetch
   * class, which defer the statement check to the moment of the execution. In
   * order to test a failure at preparation time, we have to force the
   * connection not to emulate statement preparation. Still, this is only valid
   * for the MySql driver.
   */
  public function testPrepareStatementFailOnPreparation() {
    $driver = Database::getConnection()->driver();
    if ($driver !== 'mysql') {
      $this->markTestSkipped("MySql tests can not run for driver '$driver'.");
    }

    $connection_info = Database::getConnectionInfo('default');
    $connection_info['default']['pdo'][\PDO::ATTR_EMULATE_PREPARES] = FALSE;
    Database::addConnectionInfo('default', 'foo', $connection_info['default']);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->expectException(DatabaseExceptionWrapper::class);
    $stmt = $foo_connection->prepareStatement('bananas', []);
  }

  /**
   * Tests the expected database exception thrown for inexistent tables.
   */
  public function testQueryThrowsDatabaseExceptionWrapperException() {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection()->query('SELECT * FROM {does_not_exist}');
  }

}
