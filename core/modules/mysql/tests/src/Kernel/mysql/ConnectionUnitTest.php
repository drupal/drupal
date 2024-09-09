<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\KernelTests\Core\Database\DriverSpecificConnectionUnitTestBase;

// cspell:ignore processlist

/**
 * MySQL-specific connection unit tests.
 *
 * @group Database
 */
class ConnectionUnitTest extends DriverSpecificConnectionUnitTestBase {

  /**
   * Returns a set of queries specific for MySQL.
   */
  protected function getQuery(): array {
    return [
      'connection_id' => 'SELECT CONNECTION_ID()',
      'processlist' => 'SHOW PROCESSLIST',
      'show_tables' => 'SHOW TABLES',
    ];
  }

}
