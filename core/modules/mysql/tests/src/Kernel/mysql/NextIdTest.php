<?php

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;

/**
 * Tests the sequences API.
 *
 * @group Database
 * @group legacy
 */
class NextIdTest extends DriverSpecificDatabaseTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = ['database_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $table_specification = [
      'description' => 'Stores IDs.',
      'fields' => [
        'value' => [
          'description' => 'The value of the sequence.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['value'],
    ];
    $this->connection->schema()->createTable('sequences', $table_specification);
  }

  /**
   * Tests that sequences table clear up works when a connection is closed.
   *
   * @see \Drupal\mysql\Driver\Database\mysql\Connection::__destruct()
   */
  public function testDbNextIdClosedConnection() {
    $this->expectDeprecation('Drupal\Core\Database\Connection::nextId() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345');
    $this->expectDeprecation('Drupal\mysql\Driver\Database\mysql\Connection::nextIdDelete() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345');

    // Create an additional connection to test closing the connection.
    $connection_info = Database::getConnectionInfo();
    Database::addConnectionInfo('default', 'next_id', $connection_info['default']);

    // Get a few IDs to ensure there the clean up needs to run and there is more
    // than one row.
    Database::getConnection('next_id')->nextId();
    Database::getConnection('next_id')->nextId();

    // At this point the sequences table should contain unnecessary rows.
    $count = $this->connection->select('sequences')->countQuery()->execute()->fetchField();
    $this->assertGreaterThan(1, $count);

    // Close the connection.
    Database::closeConnection('next_id');

    // Test that \Drupal\mysql\Driver\Database\mysql\Connection::__destruct()
    // successfully trims the sequences table if the connection is closed.
    $count = $this->connection->select('sequences')->countQuery()->execute()->fetchField();
    $this->assertEquals(1, $count);
  }

}
