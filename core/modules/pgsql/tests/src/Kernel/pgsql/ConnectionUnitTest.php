<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificConnectionUnitTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore processlist
/**
 * PostgreSQL-specific connection unit tests.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class ConnectionUnitTest extends DriverSpecificConnectionUnitTestBase {

  /**
   * Returns a set of queries specific for PostgreSQL.
   */
  protected function getQuery(): array {
    return [
      'connection_id' => 'SELECT pg_backend_pid()',
      'processlist' => 'SELECT pid FROM pg_stat_activity',
      'show_tables' => 'SELECT * FROM pg_catalog.pg_tables',
    ];
  }

}
