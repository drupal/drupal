<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests the sequences API.
 *
 * @group Database
 */
class NextIdTest extends DatabaseTestBase {

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
    $this->installSchema('system', 'sequences');
  }

  /**
   * Tests that the sequences API works.
   */
  public function testDbNextId() {
    $first = $this->connection->nextId();
    $second = $this->connection->nextId();
    // We can test for exact increase in here because we know there is no
    // other process operating on these tables -- normally we could only
    // expect $second > $first.
    $this->assertEquals($first + 1, $second, 'The second call from a sequence provides a number increased by one.');
    $result = $this->connection->nextId(1000);
    $this->assertEquals(1001, $result, 'Sequence provides a larger number than the existing ID.');
  }

  /**
   * Tests that sequences table clear up works when a connection is closed.
   *
   * @see \Drupal\mysql\Driver\Database\mysql\Connection::__destruct()
   */
  public function testDbNextIdClosedConnection() {
    // Only run this test for the 'mysql' driver.
    $driver = $this->connection->driver();
    if ($driver !== 'mysql') {
      $this->markTestSkipped("MySql tests can not run for driver '$driver'.");
    }
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
