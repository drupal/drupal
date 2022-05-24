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
   * Tests deprecation of Connection::prepare.
   *
   * @group legacy
   */
  public function testPrepare() {
    $this->expectDeprecation('Connection::prepare() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Database drivers should instantiate \PDOStatement objects by calling \PDO::prepare in their Connection::prepareStatement method instead. \PDO::prepare should not be called outside of driver code. See https://www.drupal.org/node/3137786');
    $connection = Database::getConnection();
    try {
      // SQLite validates the syntax upon preparing a statement already.
      // @throws \PDOException
      $query = $connection->prepare('bananas');

      // MySQL only validates the syntax upon trying to execute a query.
      // @throws \Drupal\Core\Database\DatabaseExceptionWrapper
      $connection->query($query);

      $this->fail('Expected PDOException or DatabaseExceptionWrapper, none was thrown.');
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof \PDOException || $e instanceof DatabaseExceptionWrapper, 'Exception should be an instance of \PDOException or DatabaseExceptionWrapper, thrown ' . get_class($e));
    }
  }

  /**
   * Tests deprecation of Connection::prepareQuery.
   *
   * @group legacy
   */
  public function testPrepareQuery() {
    $this->expectDeprecation('Connection::prepareQuery() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::prepareStatement() instead. See https://www.drupal.org/node/3137786');
    $this->expectException(\PDOException::class);
    $stmt = Database::getConnection()->prepareQuery('bananas');
    $stmt->execute();
  }

  /**
   * Tests deprecation of Connection::handleQueryException.
   *
   * @group legacy
   */
  public function testHandleQueryExceptionDeprecation(): void {
    $this->expectDeprecation('Passing a StatementInterface object as a $query argument to Drupal\Core\Database\Connection::query is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Call the execute method from the StatementInterface object directly instead. See https://www.drupal.org/node/3154439');
    $this->expectDeprecation('Connection::handleQueryException() is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Get a handler through $this->exceptionHandler() instead, and use one of its methods. See https://www.drupal.org/node/3187222');
    $this->expectException(DatabaseExceptionWrapper::class);
    $stmt = Database::getConnection()->prepareStatement('SELECT * FROM {does_not_exist}', []);
    Database::getConnection()->query($stmt);
  }

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
   * Tests Connection::prepareStatement with throw_exception option set.
   *
   * @group legacy
   */
  public function testPrepareStatementFailOnPreparationWithThrowExceptionOption(): void {
    $driver = Database::getConnection()->driver();
    if ($driver !== 'mysql') {
      $this->markTestSkipped("MySql tests can not run for driver '$driver'.");
    }

    $connection_info = Database::getConnectionInfo('default');
    $connection_info['default']['pdo'][\PDO::ATTR_EMULATE_PREPARES] = FALSE;
    Database::addConnectionInfo('default', 'foo', $connection_info['default']);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->expectException(DatabaseExceptionWrapper::class);
    $this->expectDeprecation('Passing a \'throw_exception\' option to %AExceptionHandler::handleStatementException is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Always catch exceptions. See https://www.drupal.org/node/3201187');
    $stmt = $foo_connection->prepareStatement('bananas', ['throw_exception' => FALSE]);
  }

  /**
   * Tests the expected database exception thrown for inexistent tables.
   */
  public function testQueryThrowsDatabaseExceptionWrapperException() {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection()->query('SELECT * FROM {does_not_exist}');
  }

}
