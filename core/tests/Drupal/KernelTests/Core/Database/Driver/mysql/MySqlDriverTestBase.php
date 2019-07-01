<?php

namespace Drupal\KernelTests\Core\Database\Driver\mysql;

use Drupal\KernelTests\Core\Database\DatabaseTestBase;

/**
 * Base class for MySql driver-specific database tests.
 */
class MySqlDriverTestBase extends DatabaseTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Only run this test for the 'mysql' driver.
    $driver = $this->connection->driver();
    if ($driver !== 'mysql') {
      $this->markTestSkipped("MySql tests can not run for driver '$driver'.");
    }
  }

}
