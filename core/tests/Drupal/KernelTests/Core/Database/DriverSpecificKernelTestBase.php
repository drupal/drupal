<?php

namespace Drupal\KernelTests\Core\Database;

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
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = Database::getConnection();

    $running_provider = $this->connection->getProvider();
    $running_driver = $this->connection->driver();
    $test_class_parts = explode('\\', get_class($this));
    $expected_provider = $test_class_parts[2] ?? '';
    for ($i = 3; $i < count($test_class_parts); $i++) {
      if ($test_class_parts[$i] === 'Kernel') {
        $expected_driver = $test_class_parts[$i + 1] ?? '';
        break;
      }
    }
    if ($running_provider !== $expected_provider || $running_driver !== $expected_driver) {
      $this->markTestSkipped("This test only runs for the database driver '$expected_driver' provided by the '$expected_provider' module. Connected database driver is '$running_driver' provided by '$running_provider'.");
    }
  }

}
