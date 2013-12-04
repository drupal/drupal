<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\LoggingTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Database;

/**
 * Tests query logging.
 */
class LoggingTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Query logging',
      'description' => 'Test the query logging facility.',
      'group' => 'Database',
    );
  }

  /**
   * Tests that we can log the existence of a query.
   */
  function testEnableLogging() {
    Database::startLog('testing');

    db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25))->fetchCol();
    db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetchCol();

    // Trigger a call that does not have file in the backtrace.
    call_user_func_array('db_query', array('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo')))->fetchCol();

    $queries = Database::getLog('testing', 'default');

    $this->assertEqual(count($queries), 3, 'Correct number of queries recorded.');

    foreach ($queries as $query) {
      $this->assertEqual($query['caller']['function'], __FUNCTION__, 'Correct function in query log.');
    }
  }

  /**
   * Tests that we can run two logs in parallel.
   */
  function testEnableMultiLogging() {
    Database::startLog('testing1');

    db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25))->fetchCol();

    Database::startLog('testing2');

    db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetchCol();

    $queries1 = Database::getLog('testing1');
    $queries2 = Database::getLog('testing2');

    $this->assertEqual(count($queries1), 2, 'Correct number of queries recorded for log 1.');
    $this->assertEqual(count($queries2), 1, 'Correct number of queries recorded for log 2.');
  }

  /**
   * Tests logging queries against multiple targets on the same connection.
   */
  function testEnableTargetLogging() {
    // Clone the master credentials to a slave connection and to another fake
    // connection.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'slave', $connection_info['default']);

    Database::startLog('testing1');

    db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25))->fetchCol();

    db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'), array('target' => 'slave'));//->fetchCol();

    $queries1 = Database::getLog('testing1');

    $this->assertEqual(count($queries1), 2, 'Recorded queries from all targets.');
    $this->assertEqual($queries1[0]['target'], 'default', 'First query used default target.');
    $this->assertEqual($queries1[1]['target'], 'slave', 'Second query used slave target.');
  }

  /**
   * Tests that logs to separate targets utilize the same connection properly.
   *
   * This test is identical to the one above, except that it doesn't create
   * a fake target so the query should fall back to running on the default
   * target.
   */
  function testEnableTargetLoggingNoTarget() {
    Database::startLog('testing1');

    db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25))->fetchCol();

    // We use "fake" here as a target because any non-existent target will do.
    // However, because all of the tests in this class share a single page
    // request there is likely to be a target of "slave" from one of the other
    // unit tests, so we use a target here that we know with absolute certainty
    // does not exist.
    db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'), array('target' => 'fake'))->fetchCol();

    $queries1 = Database::getLog('testing1');

    $this->assertEqual(count($queries1), 2, 'Recorded queries from all targets.');
    $this->assertEqual($queries1[0]['target'], 'default', 'First query used default target.');
    $this->assertEqual($queries1[1]['target'], 'default', 'Second query used default target as fallback.');
  }

  /**
   * Tests that we can log queries separately on different connections.
   */
  function testEnableMultiConnectionLogging() {
    // Clone the master credentials to a fake connection.
    // That both connections point to the same physical database is irrelevant.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('test2', 'default', $connection_info['default']);

    Database::startLog('testing1');
    Database::startLog('testing1', 'test2');

    db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25))->fetchCol();

    $old_key = db_set_active('test2');

    db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'), array('target' => 'slave'))->fetchCol();

    db_set_active($old_key);

    $queries1 = Database::getLog('testing1');
    $queries2 = Database::getLog('testing1', 'test2');

    $this->assertEqual(count($queries1), 1, 'Correct number of queries recorded for first connection.');
    $this->assertEqual(count($queries2), 1, 'Correct number of queries recorded for second connection.');
  }
}
