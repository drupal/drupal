<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Database\ConnectionTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Database;

/**
 * Tests of the core database system.
 *
 * @group Database
 */
class ConnectionTest extends DatabaseTestBase {

  /**
   * Tests that connections return appropriate connection objects.
   */
  function testConnectionRouting() {
    // Clone the primary credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertNotNull($db1, 'default connection is a real connection object.');
    $this->assertNotNull($db2, 'replica connection is a real connection object.');
    $this->assertNotIdentical($db1, $db2, 'Each target refers to a different connection.');

    // Try to open those targets another time, that should return the same objects.
    $db1b = Database::getConnection('default', 'default');
    $db2b = Database::getConnection('replica', 'default');
    $this->assertIdentical($db1, $db1b, 'A second call to getConnection() returns the same object.');
    $this->assertIdentical($db2, $db2b, 'A second call to getConnection() returns the same object.');

    // Try to open an unknown target.
    $unknown_target = $this->randomMachineName();
    $db3 = Database::getConnection($unknown_target, 'default');
    $this->assertNotNull($db3, 'Opening an unknown target returns a real connection object.');
    $this->assertIdentical($db1, $db3, 'An unknown target opens the default connection.');

    // Try to open that unknown target another time, that should return the same object.
    $db3b = Database::getConnection($unknown_target, 'default');
    $this->assertIdentical($db3, $db3b, 'A second call to getConnection() returns the same object.');
  }

  /**
   * Tests that connections return appropriate connection objects.
   */
  function testConnectionRoutingOverride() {
    // Clone the primary credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    Database::ignoreTarget('default', 'replica');

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertIdentical($db1, $db2, 'Both targets refer to the same connection.');
  }

  /**
   * Tests the closing of a database connection.
   */
  function testConnectionClosing() {
    // Open the default target so we have an object to compare.
    $db1 = Database::getConnection('default', 'default');

    // Try to close the default connection, then open a new one.
    Database::closeConnection('default', 'default');
    $db2 = Database::getConnection('default', 'default');

    // Opening a connection after closing it should yield an object different than the original.
    $this->assertNotIdentical($db1, $db2, 'Opening the default connection after it is closed returns a new object.');
  }

  /**
   * Tests the connection options of the active database.
   */
  function testConnectionOptions() {
    $connection_info = Database::getConnectionInfo('default');

    // Be sure we're connected to the default database.
    $db = Database::getConnection('default', 'default');
    $connectionOptions = $db->getConnectionOptions();

    // In the MySQL driver, the port can be different, so check individual
    // options.
    $this->assertEqual($connection_info['default']['driver'], $connectionOptions['driver'], 'The default connection info driver matches the current connection options driver.');
    $this->assertEqual($connection_info['default']['database'], $connectionOptions['database'], 'The default connection info database matches the current connection options database.');

    // Set up identical replica and confirm connection options are identical.
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);
    $db2 = Database::getConnection('replica', 'default');
    $connectionOptions2 = $db2->getConnectionOptions();

    // Get a fresh copy of the default connection options.
    $connectionOptions = $db->getConnectionOptions();
    $this->assertIdentical($connectionOptions, $connectionOptions2, 'The default and replica connection options are identical.');

    // Set up a new connection with different connection info.
    $test = $connection_info['default'];
    $test['database'] .= 'test';
    Database::addConnectionInfo('test', 'default', $test);
    $connection_info = Database::getConnectionInfo('test');

    // Get a fresh copy of the default connection options.
    $connectionOptions = $db->getConnectionOptions();
    $this->assertNotEqual($connection_info['default']['database'], $connectionOptions['database'], 'The test connection info database does not match the current connection options database.');
  }

  /**
   * Ensure that you cannot execute multiple statements on phpversion() > 5.5.21 or > 5.6.5.
   */
  public function testMultipleStatementsForNewPhp() {
    // This just tests mysql, as other PDO integrations don't allow to disable
    // multiple statements.
    if (Database::getConnection()->databaseType() !== 'mysql' || !defined('\PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
      return;
    }

    $db = Database::getConnection('default', 'default');
    try {
      $db->query('SELECT * FROM {test}; SELECT * FROM {test_people}')->execute();
      $this->fail('NO PDO exception thrown for multiple statements.');
    }
    catch (\Exception $e) {
      $this->pass('PDO exception thrown for multiple statements.');
    }
  }

}
