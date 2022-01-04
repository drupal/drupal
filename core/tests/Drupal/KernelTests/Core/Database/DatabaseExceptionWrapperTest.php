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
    $this->expectException(\PDOException::class);
    $stmt = Database::getConnection()->prepareStatement('bananas', []);
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
