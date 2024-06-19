<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests the query logging facility.
 *
 * @coversDefaultClass \Drupal\Core\Database\Log
 *
 * @group Database
 */
class LoggingTest extends DatabaseTestBase {

  /**
   * Tests that we can log the existence of a query.
   */
  public function testEnableLogging(): void {
    Database::startLog('testing');

    $start = microtime(TRUE);
    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();
    $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    // Trigger a call that does not have file in the backtrace.
    call_user_func_array([Database::getConnection(), 'query'], ['SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo']])->fetchCol();

    $queries = Database::getLog('testing', 'default');

    $this->assertCount(3, $queries, 'Correct number of queries recorded.');

    foreach ($queries as $query) {
      $this->assertEquals(__FUNCTION__, $query['caller']['function'], 'Correct function in query log.');
      $this->assertIsFloat($query['start']);
      $this->assertGreaterThanOrEqual($start, $query['start']);
    }
  }

  /**
   * Tests that we can run two logs in parallel.
   */
  public function testEnableMultiLogging(): void {
    Database::startLog('testing1');

    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();

    Database::startLog('testing2');

    $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    $queries1 = Database::getLog('testing1');
    $queries2 = Database::getLog('testing2');

    $this->assertCount(2, $queries1, 'Correct number of queries recorded for log 1.');
    $this->assertCount(1, $queries2, 'Correct number of queries recorded for log 2.');
  }

  /**
   * Tests logging queries against multiple targets on the same connection.
   */
  public function testEnableTargetLogging(): void {
    // Clone the primary credentials to a replica connection and to another fake
    // connection.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    Database::startLog('testing1');

    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();

    Database::getConnection('replica')->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    $queries1 = Database::getLog('testing1');

    $this->assertCount(2, $queries1, 'Recorded queries from all targets.');
    $this->assertEquals('default', $queries1[0]['target'], 'First query used default target.');
    $this->assertEquals('replica', $queries1[1]['target'], 'Second query used replica target.');
  }

  /**
   * Tests that logs to separate targets use the same connection properly.
   *
   * This test is identical to the one above, except that it doesn't create
   * a fake target so the query should fall back to running on the default
   * target.
   */
  public function testEnableTargetLoggingNoTarget(): void {
    Database::startLog('testing1');

    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();

    // We use "fake" here as a target because any non-existent target will do.
    // However, because all of the tests in this class share a single page
    // request there is likely to be a target of "replica" from one of the other
    // unit tests, so we use a target here that we know with absolute certainty
    // does not exist.
    Database::getConnection('fake')->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    $queries1 = Database::getLog('testing1');

    $this->assertCount(2, $queries1, 'Recorded queries from all targets.');
    $this->assertEquals('default', $queries1[0]['target'], 'First query used default target.');
    $this->assertEquals('default', $queries1[1]['target'], 'Second query used default target as fallback.');
  }

  /**
   * Tests that we can log queries separately on different connections.
   */
  public function testEnableMultiConnectionLogging(): void {
    // Clone the primary credentials to a fake connection.
    // That both connections point to the same physical database is irrelevant.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('test2', 'default', $connection_info['default']);

    Database::startLog('testing1');
    Database::startLog('testing1', 'test2');

    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();

    $old_key = Database::setActiveConnection('test2');

    Database::getConnection('replica')->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    Database::setActiveConnection($old_key);

    $queries1 = Database::getLog('testing1');
    $queries2 = Database::getLog('testing1', 'test2');

    $this->assertCount(1, $queries1, 'Correct number of queries recorded for first connection.');
    $this->assertCount(1, $queries2, 'Correct number of queries recorded for second connection.');
  }

  /**
   * Tests that getLog with a wrong key return an empty array.
   */
  public function testGetLoggingWrongKey(): void {
    $result = Database::getLog('wrong');

    $this->assertEquals([], $result, 'The function getLog with a wrong key returns an empty array.');
  }

}
