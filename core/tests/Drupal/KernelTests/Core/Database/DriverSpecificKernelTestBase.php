<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

// cSpell:ignore mymodule mydriver

/**
 * Base class for driver specific kernel tests.
 *
 * Driver specific tests should be created in the
 * \Drupal\Tests\mymodule\Kernel\mydriver namespace, and their execution will
 * only occur when the database driver of the SUT is provided by 'mymodule' and
 * named 'mydriver'.
 */
abstract class DriverSpecificKernelTestBase extends KernelTestBase {

  /**
   * The database connection for testing.
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Find the current SUT database driver from the connection info. If that
    // is not the one the test requires, skip before test database
    // initialization so to save cycles.
    $this->root = static::getDrupalRoot();
    $connectionInfo = $this->getDatabaseConnectionInfo();
    $test_class_parts = explode('\\', get_class($this));
    $expected_provider = $test_class_parts[2] ?? '';
    for ($i = 3; $i < count($test_class_parts); $i++) {
      if ($test_class_parts[$i] === 'Kernel') {
        $expected_driver = $test_class_parts[$i + 1] ?? '';
        break;
      }
    }
    if ($connectionInfo['default']['driver'] !== $expected_driver) {
      $this->markTestSkipped("This test only runs for the database driver '$expected_driver'. Current database driver is '{$connectionInfo['default']['driver']}'.");
    }

    parent::setUp();
    $this->connection = Database::getConnection();

    // After database initialization, the database driver may be not provided
    // by the expected module; skip test in that case.
    $running_provider = $this->connection->getProvider();
    $running_driver = $this->connection->driver();
    if ($running_provider !== $expected_provider || $running_driver !== $expected_driver) {
      $this->markTestSkipped("This test only runs for the database driver '$expected_driver' provided by the '$expected_provider' module. Connected database driver is '$running_driver' provided by '$running_provider'.");
    }
  }

}
