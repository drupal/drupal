<?php

/**
 * @file
 * Contains Drupal\system\Tests\Database\ConnectionUnitTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Database;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests management of database connections.
 */
class ConnectionUnitTest extends UnitTestBase {

  protected $key;
  protected $target;

  protected $monitor;
  protected $originalCount;

  public static function getInfo() {
    return array(
      'name' => 'Connection unit tests',
      'description' => 'Tests management of database connections.',
      'group' => 'Database',
    );
  }

  function setUp() {
    parent::setUp();

    $this->key = 'default';
    $this->originalTarget = 'default';
    $this->target = 'DatabaseConnectionUnitTest';

    // Create an additional connection to monitor the connections being opened
    // and closed in this test.
    // @see TestBase::changeDatabasePrefix()
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'monitor', $connection_info['default']);
    global $databases;
    $databases['default']['monitor'] = $connection_info['default'];
    $this->monitor = Database::getConnection('monitor');
  }

  /**
   * Adds a new database connection info to Database.
   */
  protected function addConnection() {
    // Add a new target to the connection, by cloning the current connection.
    $connection_info = Database::getConnectionInfo($this->key);
    Database::addConnectionInfo($this->key, $this->target, $connection_info[$this->originalTarget]);

    // Verify that the new target exists.
    $info = Database::getConnectionInfo($this->key);
    // Note: Custom assertion message to not expose database credentials.
    $this->assertIdentical($info[$this->target], $connection_info[$this->key], 'New connection info found.');
  }

  /**
   * Returns the connection ID of the current test connection.
   *
   * @return integer
   */
  protected function getConnectionID() {
    return (int) Database::getConnection($this->target, $this->key)->query('SELECT CONNECTION_ID()')->fetchField();
  }

  /**
   * Asserts that a connection ID exists.
   *
   * @param integer $id
   *   The connection ID to verify.
   */
  protected function assertConnection($id) {
    $list = $this->monitor->query('SHOW PROCESSLIST')->fetchAllKeyed(0, 0);
    return $this->assertTrue(isset($list[$id]), format_string('Connection ID @id found.', array('@id' => $id)));
  }

  /**
   * Asserts that a connection ID does not exist.
   *
   * @param integer $id
   *   The connection ID to verify.
   */
  protected function assertNoConnection($id) {
    $list = $this->monitor->query('SHOW PROCESSLIST')->fetchAllKeyed(0, 0);
    return $this->assertFalse(isset($list[$id]), format_string('Connection ID @id not found.', array('@id' => $id)));
  }

  /**
   * Tests Database::closeConnection() without query.
   *
   * @todo getConnectionID() executes a query.
   */
  function testOpenClose() {
    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionID();
    Database::getConnection($this->target, $this->key);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Close the connection.
    Database::closeConnection($this->target, $this->key);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a query.
   */
  function testOpenQueryClose() {
    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionID();
    Database::getConnection($this->target, $this->key);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Execute a query.
    Database::getConnection($this->target, $this->key)->query('SHOW TABLES');

    // Close the connection.
    Database::closeConnection($this->target, $this->key);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a query and custom prefetch method.
   */
  function testOpenQueryPrefetchClose() {
    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionID();
    Database::getConnection($this->target, $this->key);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Execute a query.
    Database::getConnection($this->target, $this->key)->query('SHOW TABLES')->fetchCol();

    // Close the connection.
    Database::closeConnection($this->target, $this->key);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

  /**
   * Tests Database::closeConnection() with a select query.
   */
  function testOpenSelectQueryClose() {
    // Add and open a new connection.
    $this->addConnection();
    $id = $this->getConnectionID();
    Database::getConnection($this->target, $this->key);

    // Verify that there is a new connection.
    $this->assertConnection($id);

    // Create a table.
    $name = 'foo';
    Database::getConnection($this->target, $this->key)->schema()->createTable($name, array(
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    ));

    // Execute a query.
    Database::getConnection($this->target, $this->key)->select('foo', 'f')
      ->fields('f', array('name'))
      ->execute()
      ->fetchAll();

    // Drop the table.
    Database::getConnection($this->target, $this->key)->schema()->dropTable($name);

    // Close the connection.
    Database::closeConnection($this->target, $this->key);
    // Wait 20ms to give the database engine sufficient time to react.
    usleep(20000);

    // Verify that we are back to the original connection count.
    $this->assertNoConnection($id);
  }

}
