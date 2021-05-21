<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Log;
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
  public function testEnableLogging() {
    Database::startLog('testing');

    $this->connection->query('SELECT [name] FROM {test} WHERE [age] > :age', [':age' => 25])->fetchCol();
    $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchCol();

    // Trigger a call that does not have file in the backtrace.
    call_user_func_array([Database::getConnection(), 'query'], ['SELECT age FROM {test} WHERE name = :name', [':name' => 'Ringo']])->fetchCol();

    $queries = Database::getLog('testing', 'default');

    $this->assertCount(3, $queries, 'Correct number of queries recorded.');

    foreach ($queries as $query) {
      $this->assertEquals(__FUNCTION__, $query['caller']['function'], 'Correct function in query log.');
    }
  }

  /**
   * Tests that we can run two logs in parallel.
   */
  public function testEnableMultiLogging() {
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
  public function testEnableTargetLogging() {
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
  public function testEnableTargetLoggingNoTarget() {
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
  public function testEnableMultiConnectionLogging() {
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
  public function testGetLoggingWrongKey() {
    $result = Database::getLog('wrong');

    $this->assertEquals([], $result, 'The function getLog with a wrong key returns an empty array.');
  }

  /**
   * Tests that a log called by a custom database driver returns proper caller.
   *
   * @param string $driver_namespace
   *   The driver namespace to be tested.
   * @param string $stack
   *   A test debug_backtrace stack.
   * @param array $expected_entry
   *   The expected stack entry.
   *
   * @covers ::findCaller
   *
   * @dataProvider providerContribDriverLog
   */
  public function testContribDriverLog($driver_namespace, $stack, array $expected_entry) {
    $mock_builder = $this->getMockBuilder(Log::class);
    $log = $mock_builder
      ->setMethods(['getDebugBacktrace'])
      ->setConstructorArgs(['test'])
      ->getMock();
    $log->expects($this->once())
      ->method('getDebugBacktrace')
      ->will($this->returnValue($stack));
    Database::addConnectionInfo('test', 'default', ['driver' => 'mysql', 'namespace' => $driver_namespace]);

    $result = $log->findCaller($stack);
    $this->assertEquals($expected_entry, $result);
  }

  /**
   * Provides data for the testContribDriverLog test.
   *
   * @return array[]
   *   A associative array of simple arrays, each having the following elements:
   *   - the contrib driver PHP namespace
   *   - a test debug_backtrace stack
   *   - the stack entry expected to be returned.
   *
   * @see ::testContribDriverLog()
   */
  public function providerContribDriverLog() {
    $stack = [
      [
        'file' => '/var/www/core/lib/Drupal/Core/Database/Log.php',
        'line' => 125,
        'function' => 'findCaller',
        'class' => 'Drupal\\Core\\Database\\Log',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/libraries/drudbal/lib/Statement.php',
        'line' => 264,
        'function' => 'log',
        'class' => 'Drupal\\Core\\Database\\Log',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/libraries/drudbal/lib/Connection.php',
        'line' => 213,
        'function' => 'execute',
        'class' => 'Drupal\\Driver\\Database\\dbal\\Statement',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/core/tests/Drupal/KernelTests/Core/Database/LoggingTest.php',
        'line' => 23,
        'function' => 'query',
        'class' => 'Drupal\\Driver\\Database\\dbal\\Connection',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 1154,
        'function' => 'testEnableLogging',
        'class' => 'Drupal\\KernelTests\\Core\\Database\\LoggingTest',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 842,
        'function' => 'runTest',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestResult.php',
        'line' => 693,
        'function' => 'runBare',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 796,
        'function' => 'run',
        'class' => 'PHPUnit\\Framework\\TestResult',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => 'Standard input code',
        'line' => 57,
        'function' => 'run',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => 'Standard input code',
        'line' => 111,
        'function' => '__phpunit_run_isolated_test',
        'args' => [
          0 => 'test',
        ],
      ],
    ];

    return [
      // Test that if the driver namespace is in the stack trace, the first
      // non-database entry is returned.
      'contrib driver namespace' => [
        'Drupal\\Driver\\Database\\dbal',
        $stack,
        [
          'class' => 'Drupal\\KernelTests\\Core\\Database\\LoggingTest',
          'function' => 'testEnableLogging',
          'file' => '/var/www/core/tests/Drupal/KernelTests/Core/Database/LoggingTest.php',
          'line' => 23,
          'type' => '->',
          'args' => [
            0 => 'test',
          ],
        ],
      ],
      // Extreme case, should not happen at normal runtime - if the driver
      // namespace is not in the stack trace, the first entry to a method
      // in core database namespace is returned.
      'missing driver namespace' => [
        'Drupal\\Driver\\Database\\fake',
        $stack,
        [
          'class' => 'Drupal\\Driver\\Database\\dbal\\Statement',
          'function' => 'execute',
          'file' => '/var/www/libraries/drudbal/lib/Statement.php',
          'line' => 264,
          'type' => '->',
          'args' => [
            0 => 'test',
          ],
        ],
      ],
    ];
  }

}
