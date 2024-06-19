<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;

/**
 * Tests that the prefix info for a database schema is correct.
 *
 * @group Database
 */
class PrefixInfoTest extends DriverSpecificKernelTestBase {

  /**
   * Tests that DatabaseSchema::getPrefixInfo() returns the right database.
   *
   * We are testing if the return array of the method
   * \Drupal\mysql\Driver\Database\mysql\Schema::getPrefixInfo(). This return
   * array is a keyed array with info about amongst other things the database.
   * The other two by Drupal core supported databases do not have this variable
   * set in the return array.
   */
  public function testGetPrefixInfo(): void {
    $connection_info = Database::getConnectionInfo('default');

    // Copy the default connection info to the 'extra' key.
    Database::addConnectionInfo('extra', 'default', $connection_info['default']);

    $db1_connection = Database::getConnection('default', 'default');
    $db1_schema = $db1_connection->schema();
    $db2_connection = Database::getConnection('default', 'extra');

    // Get the prefix info for the first database.
    $method = new \ReflectionMethod($db1_schema, 'getPrefixInfo');
    $db1_info = $method->invoke($db1_schema);

    // We change the database after opening the connection, so as to prevent
    // connecting to a non-existent database.
    $reflection = new \ReflectionObject($db2_connection);
    $property = $reflection->getProperty('connectionOptions');
    $connection_info['default']['database'] = 'foobar';
    $property->setValue($db2_connection, $connection_info['default']);

    // For testing purposes, we also change the database info.
    $reflection_class = new \ReflectionClass(Database::class);
    $property = $reflection_class->getProperty('databaseInfo');
    $info = $property->getValue();
    $info['extra']['default']['database'] = 'foobar';
    $property->setValue(NULL, $info);

    $extra_info = Database::getConnectionInfo('extra');
    $this->assertSame('foobar', $extra_info['default']['database']);
    $db2_schema = $db2_connection->schema();
    $db2_info = $method->invoke($db2_schema);

    // Each target connection has a different database.
    $this->assertNotSame($db2_info['database'], $db1_info['database']);
    // The new profile has a different database.
    $this->assertSame('foobar', $db2_info['database']);

    Database::removeConnection('extra');
  }

}
