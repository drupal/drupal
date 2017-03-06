<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests that the prefix info for a database schema is correct.
 *
 * @group Database
 */
class PrefixInfoTest extends DatabaseTestBase {

  /**
   * Tests that DatabaseSchema::getPrefixInfo() returns the right database.
   *
   * We are testing if the return array of the method
   * \Drupal\Core\Database\Driver\mysql\Schema::getPrefixInfo(). This return
   * array is a keyed array with info about amongst other things the database.
   * The other two by Drupal core supported databases do not have this variable
   * set in the return array.
   */
  public function testGetPrefixInfo() {
    $connection_info = Database::getConnectionInfo('default');
    if ($connection_info['default']['driver'] == 'mysql') {
      // Copy the default connection info to the 'extra' key.
      Database::addConnectionInfo('extra', 'default', $connection_info['default']);

      $db1_connection = Database::getConnection('default', 'default');
      $db1_schema = $db1_connection->schema();
      $db2_connection = Database::getConnection('default', 'extra');

      // Get the prefix info for the first databse.
      $method = new \ReflectionMethod($db1_schema, 'getPrefixInfo');
      $method->setAccessible(TRUE);
      $db1_info = $method->invoke($db1_schema);

      // We change the database after opening the connection, so as to prevent
      // connecting to a non-existent database.
      $reflection = new \ReflectionObject($db2_connection);
      $property = $reflection->getProperty('connectionOptions');
      $property->setAccessible(TRUE);
      $connection_info['default']['database'] = 'foobar';
      $property->setValue($db2_connection, $connection_info['default']);

      // For testing purposes, we also change the database info.
      $reflection_class = new \ReflectionClass('Drupal\Core\Database\Database');
      $property = $reflection_class->getProperty('databaseInfo');
      $property->setAccessible(TRUE);
      $info = $property->getValue();
      $info['extra']['default']['database'] = 'foobar';
      $property->setValue(NULL, $info);

      $extra_info = Database::getConnectionInfo('extra');
      $this->assertSame($extra_info['default']['database'], 'foobar');
      $db2_schema = $db2_connection->schema();
      $db2_info = $method->invoke($db2_schema);

      $this->assertNotSame($db2_info['database'], $db1_info['database'], 'Each target connection has a different database.');
      $this->assertSame($db2_info['database'], 'foobar', 'The new profile has a different database.');

      Database::removeConnection('extra');
    }
  }

}
