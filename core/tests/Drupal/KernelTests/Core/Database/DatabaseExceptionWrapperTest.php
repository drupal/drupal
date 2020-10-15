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
   * Tests Connection::prepareStatement exceptions.
   */
  public function testPrepareStatement() {
    $this->expectException(\PDOException::class);
    $stmt = Database::getConnection()->prepareStatement('bananas', []);
    $stmt->execute();
  }

  /**
   * Tests the expected database exception thrown for inexistent tables.
   */
  public function testQueryThrowsDatabaseExceptionWrapperException() {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection()->query('SELECT * FROM {does_not_exist}');
  }

}
