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
   * @expectedDeprecation Connection::prepare() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Database drivers should instantiate \PDOStatement objects by calling \PDO::prepare in their Collection::prepareStatement method instead. \PDO::prepare should not be called outside of driver code. See https://www.drupal.org/node/3137786
   */
  public function testPrepare() {
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
    catch (\PDOException $e) {
      $this->pass('Expected PDOException was thrown.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->pass('Expected DatabaseExceptionWrapper was thrown.');
    }
    catch (\Exception $e) {
      $this->fail("Thrown exception is not a PDOException:\n" . (string) $e);
    }
  }

  /**
   * Tests deprecation of Connection::prepareQuery.
   *
   * @group legacy
   * @expectedDeprecation Connection::prepareQuery() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::prepareStatement() instead. See https://www.drupal.org/node/3137786
   */
  public function testPrepareQuery() {
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
    $connection = Database::getConnection();
    try {
      $connection->query('SELECT * FROM {does_not_exist}');
      $this->fail('Expected PDOException, none was thrown.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->pass('Expected DatabaseExceptionWrapper was thrown.');
    }
    catch (\Exception $e) {
      $this->fail("Thrown exception is not a DatabaseExceptionWrapper:\n" . (string) $e);
    }
  }

}
