<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\StatementWrapper;

/**
 * Tests of the core database system.
 *
 * @group Database
 */
class ConnectionTest extends DatabaseTestBase {

  /**
   * Tests that connections return appropriate connection objects.
   */
  public function testConnectionRouting() {
    // Clone the primary credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertNotNull($db1, 'default connection is a real connection object.');
    $this->assertNotNull($db2, 'replica connection is a real connection object.');
    $this->assertNotSame($db1, $db2, 'Each target refers to a different connection.');

    // Try to open those targets another time, that should return the same objects.
    $db1b = Database::getConnection('default', 'default');
    $db2b = Database::getConnection('replica', 'default');
    $this->assertSame($db1, $db1b, 'A second call to getConnection() returns the same object.');
    $this->assertSame($db2, $db2b, 'A second call to getConnection() returns the same object.');

    // Try to open an unknown target.
    $unknown_target = $this->randomMachineName();
    $db3 = Database::getConnection($unknown_target, 'default');
    $this->assertNotNull($db3, 'Opening an unknown target returns a real connection object.');
    $this->assertSame($db1, $db3, 'An unknown target opens the default connection.');

    // Try to open that unknown target another time, that should return the same object.
    $db3b = Database::getConnection($unknown_target, 'default');
    $this->assertSame($db3, $db3b, 'A second call to getConnection() returns the same object.');
  }

  /**
   * Tests that connections return appropriate connection objects.
   */
  public function testConnectionRoutingOverride() {
    // Clone the primary credentials to a replica connection.
    // Note this will result in two independent connection objects that happen
    // to point to the same place.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);

    Database::ignoreTarget('default', 'replica');

    $db1 = Database::getConnection('default', 'default');
    $db2 = Database::getConnection('replica', 'default');

    $this->assertSame($db1, $db2, 'Both targets refer to the same connection.');
  }

  /**
   * Tests the closing of a database connection.
   */
  public function testConnectionClosing() {
    // Open the default target so we have an object to compare.
    $db1 = Database::getConnection('default', 'default');

    // Try to close the default connection, then open a new one.
    Database::closeConnection('default', 'default');
    $db2 = Database::getConnection('default', 'default');

    // Opening a connection after closing it should yield an object different than the original.
    $this->assertNotSame($db1, $db2, 'Opening the default connection after it is closed returns a new object.');
  }

  /**
   * Tests the connection options of the active database.
   */
  public function testConnectionOptions() {
    $connection_info = Database::getConnectionInfo('default');

    // Be sure we're connected to the default database.
    $db = Database::getConnection('default', 'default');
    $connectionOptions = $db->getConnectionOptions();

    // In the MySQL driver, the port can be different, so check individual
    // options.
    $this->assertEquals($connection_info['default']['driver'], $connectionOptions['driver'], 'The default connection info driver matches the current connection options driver.');
    $this->assertEquals($connection_info['default']['database'], $connectionOptions['database'], 'The default connection info database matches the current connection options database.');

    // Set up identical replica and confirm connection options are identical.
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);
    $db2 = Database::getConnection('replica', 'default');
    // Getting a driver class ensures the namespace option is set.
    $this->assertEquals($db->getDriverClass('Select'), $db2->getDriverClass('Select'));
    $connectionOptions2 = $db2->getConnectionOptions();

    // Get a fresh copy of the default connection options.
    $connectionOptions = $db->getConnectionOptions();
    $this->assertSame($connectionOptions2, $connectionOptions, 'The default and replica connection options are identical.');

    // Set up a new connection with different connection info.
    $test = $connection_info['default'];
    $test['database'] .= 'test';
    Database::addConnectionInfo('test', 'default', $test);
    $connection_info = Database::getConnectionInfo('test');

    // Get a fresh copy of the default connection options.
    $connectionOptions = $db->getConnectionOptions();
    $this->assertNotEquals($connection_info['default']['database'], $connectionOptions['database'], 'The test connection info database does not match the current connection options database.');
  }

  /**
   * Tests the deprecation of the 'transactions' connection option.
   *
   * @group legacy
   */
  public function testTransactionsOptionDeprecation() {
    $this->expectDeprecation('Passing a \'transactions\' connection option to Drupal\Core\Database\Connection::__construct is deprecated in drupal:9.1.0 and is removed in drupal:10.0.0. All database drivers must support transactions. See https://www.drupal.org/node/2278745');
    $this->expectDeprecation('Drupal\Core\Database\Connection::supportsTransactions is deprecated in drupal:9.1.0 and is removed in drupal:10.0.0. All database drivers must support transactions. See https://www.drupal.org/node/2278745');
    $connection_info = Database::getConnectionInfo('default');
    $connection_info['default']['transactions'] = FALSE;
    Database::addConnectionInfo('default', 'foo', $connection_info['default']);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->assertInstanceOf(Connection::class, $foo_connection);
    $this->assertTrue($foo_connection->supportsTransactions());
  }

  /**
   * Tests the deprecation of passing a statement object to ::query.
   *
   * @group legacy
   */
  public function testStatementQueryDeprecation(): void {
    $this->expectDeprecation('Passing a StatementInterface object as a $query argument to Drupal\Core\Database\Connection::query is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Call the execute method from the StatementInterface object directly instead. See https://www.drupal.org/node/3154439');
    $db = Database::getConnection();
    $stmt = $db->prepareStatement('SELECT * FROM {test}', []);
    $this->assertNotNull($db->query($stmt));
  }

  /**
   * Tests the deprecation of passing a PDOStatement object to ::query.
   *
   * @group legacy
   */
  public function testPDOStatementQueryDeprecation(): void {
    $db = Database::getConnection();
    $stmt = $db->prepareStatement('SELECT * FROM {test}', []);
    if (!$stmt instanceof StatementWrapper) {
      $this->markTestSkipped("This test only runs for db drivers using StatementWrapper.");
    }
    if (!$stmt->getClientStatement() instanceof \PDOStatement) {
      $this->markTestSkipped("This test only runs for PDO-based db drivers.");
    }
    $this->expectDeprecation('Passing a \\PDOStatement object as a $query argument to Drupal\Core\Database\Connection::query is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Call the execute method from the StatementInterface object directly instead. See https://www.drupal.org/node/3154439');
    $this->assertNotNull($db->query($stmt->getClientStatement()));
  }

  /**
   * Tests per-table prefix connection option.
   */
  public function testPerTablePrefixOption() {
    $connection_info = Database::getConnectionInfo('default');
    $new_connection_info = $connection_info['default'];
    $new_connection_info['prefix'] = [
      'default' => $connection_info['default']['prefix'],
      'test_table' => $connection_info['default']['prefix'] . '_bar',
    ];
    Database::addConnectionInfo('default', 'foo', $new_connection_info);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->assertInstanceOf(Connection::class, $foo_connection);
    $this->assertIsString($foo_connection->getConnectionOptions()['prefix']);
    $this->assertSame($connection_info['default']['prefix'], $foo_connection->getConnectionOptions()['prefix']);
    $this->assertSame([
      'test_table' => $connection_info['default']['prefix'] . '_bar',
    ], $foo_connection->getConnectionOptions()['extra_prefix']);
  }

  /**
   * Tests the prefix connection option in array form.
   */
  public function testPrefixArrayOption() {
    $connection_info = Database::getConnectionInfo('default');
    $new_connection_info = $connection_info['default'];
    $new_connection_info['prefix'] = [
      'default' => $connection_info['default']['prefix'],
    ];
    Database::addConnectionInfo('default', 'foo', $new_connection_info);
    $foo_connection = Database::getConnection('foo', 'default');
    $this->assertInstanceOf(Connection::class, $foo_connection);
    $this->assertIsString($foo_connection->getConnectionOptions()['prefix']);
    $this->assertSame($connection_info['default']['prefix'], $foo_connection->getConnectionOptions()['prefix']);
    $this->assertArrayNotHasKey('extra_prefix', $foo_connection->getConnectionOptions());
  }

  /**
   * Ensure that you cannot execute multiple statements on MySQL.
   */
  public function testMultipleStatementsForNewPhp() {
    // This just tests mysql, as other PDO integrations don't allow disabling
    // multiple statements.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->markTestSkipped("This test only runs for MySQL");
    }

    // Disable the protection at the PHP level.
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection('default', 'default')->query('SELECT * FROM {test}; SELECT * FROM {test_people}', [], ['allow_delimiter_in_query' => TRUE]);
  }

  /**
   * Ensure that you cannot execute multiple statements in a query.
   */
  public function testMultipleStatementsQuery() {
    $this->expectException(\InvalidArgumentException::class);
    Database::getConnection('default', 'default')->query('SELECT * FROM {test}; SELECT * FROM {test_people}');
  }

  /**
   * Ensure that you cannot prepare multiple statements.
   */
  public function testMultipleStatements() {
    $this->expectException(\InvalidArgumentException::class);
    Database::getConnection('default', 'default')->prepareStatement('SELECT * FROM {test}; SELECT * FROM {test_people}', []);
  }

  /**
   * Tests that the method ::condition() returns a Condition object.
   */
  public function testCondition() {
    $connection = Database::getConnection('default', 'default');
    $namespace = (new \ReflectionObject($connection))->getNamespaceName() . "\\Condition";
    if (!class_exists($namespace)) {
      $namespace = Condition::class;
    }
    $condition = $connection->condition('AND');
    $this->assertSame($namespace, get_class($condition));
  }

}
