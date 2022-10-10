<?php

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;

/**
 * Tests exceptions thrown by queries.
 *
 * @group Database
 */
class DatabaseExceptionWrapperTest extends DriverSpecificKernelTestBase {

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
    $connection_info = Database::getConnectionInfo('default');
    $connection_info['default']['pdo'][\PDO::ATTR_EMULATE_PREPARES] = FALSE;
    Database::addConnectionInfo('default', 'foo', $connection_info['default']);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->expectException(DatabaseExceptionWrapper::class);
    $stmt = $foo_connection->prepareStatement('bananas', []);
  }

  /**
   * Tests Connection::prepareStatement exception on execution.
   */
  public function testPrepareStatementFailOnExecution() {
    $this->expectException(\PDOException::class);
    $stmt = $this->connection->prepareStatement('bananas', []);
    $stmt->execute();
  }

}
