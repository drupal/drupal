<?php

/**
 * @file
 * Contains Drupal\system\Tests\Database\ConnectionUnitTest.
 */

namespace Drupal\system\Tests\Database;

use Doctrine\Common\Reflection\StaticReflectionProperty;
use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests management of database connections.
 *
 * @group Database
 */
class ConnectionUnitTest extends KernelTestBase {

  protected $key;
  protected $target;

  protected $monitor;
  protected $originalCount;

  protected function setUp() {
    parent::setUp();

    $this->key = 'default';
    $this->originalTarget = 'default';
    $this->target = 'DatabaseConnectionUnitTest';

    // Determine whether the database driver is MySQL. If it is not, the test
    // methods will not be executed.
    // @todo Make this test driver-agnostic, or find a proper way to skip it.
    // @see http://drupal.org/node/1273478
    $connection_info = Database::getConnectionInfo('default');
    $this->skipTest = (bool) ($connection_info['default']['driver'] != 'mysql');
    if ($this->skipTest) {
      // Insert an assertion to prevent Simpletest from interpreting the test
      // as failure.
      $this->pass('This test is only compatible with MySQL.');
    }

    // Create an additional connection to monitor the connections being opened
    // and closed in this test.
    // @see TestBase::changeDatabasePrefix()
    Database::addConnectionInfo('default', 'monitor', $connection_info['default']);
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
    if ($this->skipTest) {
      return;
    }
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
    if ($this->skipTest) {
      return;
    }
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
    if ($this->skipTest) {
      return;
    }
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
    if ($this->skipTest) {
      return;
    }
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

  /**
   * Tests the serialization and unserialization of a database connection.
   */
  public function testConnectionSerialization() {
    $db = Database::getConnection('default', 'default');

    try {
      $serialized = serialize($db);
      $this->pass('The database connection can be serialized.');

      $unserialized = unserialize($serialized);
      $this->assertTrue(get_class($unserialized) === get_class($db));
    }
    catch (\Exception $e) {
      $this->fail('The database connection cannot be serialized.');
    }

    // Ensure that all properties on the unserialized object are the same.
    $db_reflection = new \ReflectionObject($db);
    $unserialized_reflection = new \ReflectionObject($unserialized);
    foreach ($db_reflection->getProperties() as $value) {
      $value->setAccessible(TRUE);

      // Skip properties that are lazily populated on access.
      if ($value->getName() === 'driverClasses' || $value->getName() === 'schema') {
        continue;
      }

      $unserialized_property = $unserialized_reflection->getProperty($value->getName());
      $unserialized_property->setAccessible(TRUE);
      // For the PDO object, just check the statement class attribute.
      if ($value->getName() == 'connection') {
        $db_statement_class = $unserialized_property->getValue($db)->getAttribute(\PDO::ATTR_STATEMENT_CLASS);
        $unserialized_statement_class = $unserialized_property->getValue($unserialized)->getAttribute(\PDO::ATTR_STATEMENT_CLASS);
        // Assert the statement class.
        $this->assertEqual($unserialized_statement_class[0], $db_statement_class[0]);
        // Assert the connection argument that is passed into the statement.
        $this->assertEqual(get_class($unserialized_statement_class[1][0]), get_class($db_statement_class[1][0]));
      }
      else {
        $actual = $unserialized_property->getValue($unserialized);
        $expected = $value->getValue($db);
        $this->assertEqual($actual, $expected, vsprintf('Unserialized Connection property %s value %s is equal to expected %s', array(
          var_export($value->getName(), TRUE),
          is_object($actual) ? print_r($actual, TRUE) : var_export($actual, TRUE),
          is_object($expected) ? print_r($expected, TRUE) : var_export($expected, TRUE),
        )));
      }
    }

    // By using "key", we ensure that its not a key used in the serialized PHP.
    $not_serialized_properties = ['"connection"', '"connectionOptions"', '"schema"', '"prefixes"', '"prefixReplace"', '"driverClasses"'];
    foreach ($not_serialized_properties as $property) {
      $this->assertIdentical(FALSE, strpos($serialized, $property));
    }

    // Serialize the DB connection again, but this time change the connection
    // information under the hood.
    $serialized = serialize($db);
    $db_connection_info = Database::getAllConnectionInfo();

    // Use reflection to empty out $databaseInfo.
    $reflection_class = new \ReflectionClass('Drupal\Core\Database\Database');
    $database_info_reflection = $reflection_class->getProperty('databaseInfo');
    $database_info_reflection->setAccessible(TRUE);
    $database_info_reflection->setValue(NULL, []);

    // Setup a different DB connection which should be picked up after the
    // unserialize.
    $db_connection_info['default']['default']['extra'] = 'value';

    Database::setMultipleConnectionInfo($db_connection_info);

    /** @var \Drupal\Core\Database\Connection $db */
    $db = unserialize($serialized);
    $this->assertEqual($db->getConnectionOptions()['extra'], 'value');
  }

  /**
   * Tests pdo options override.
   */
  public function testConnectionOpen() {
    $connection = Database::getConnection('default');
    $reflection = new \ReflectionObject($connection);
    $connection_property = $reflection->getProperty('connection');
    $connection_property->setAccessible(TRUE);
    $error_mode = $connection_property->getValue($connection)
      ->getAttribute(\PDO::ATTR_ERRMODE);
    $this->assertEqual($error_mode, \PDO::ERRMODE_EXCEPTION, 'Ensure the default error mode is set to exception.');

    $connection = Database::getConnectionInfo('default');
    $connection['default']['pdo'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_SILENT;
    Database::addConnectionInfo('test', 'default', $connection['default']);
    $connection = Database::getConnection('default', 'test');

    $reflection = new \ReflectionObject($connection);
    $connection_property = $reflection->getProperty('connection');
    $connection_property->setAccessible(TRUE);
    $error_mode = $connection_property->getValue($connection)
      ->getAttribute(\PDO::ATTR_ERRMODE);
    $this->assertEqual($error_mode, \PDO::ERRMODE_SILENT, 'Ensure PDO connection options can be overridden.');

    Database::removeConnection('test');
  }

}
