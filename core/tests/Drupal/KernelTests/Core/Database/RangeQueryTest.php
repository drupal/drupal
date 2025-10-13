<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Range query functionality.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class RangeQueryTest extends DatabaseTestBase {

  /**
   * Confirms that range queries work and return the correct result.
   */
  public function testRangeQuery(): void {
    // Test if return correct number of rows.
    $range_rows = $this->connection->queryRange("SELECT [name] FROM {test} ORDER BY [name]", 1, 3)->fetchAll();
    $this->assertCount(3, $range_rows, 'Range query work and return correct number of rows.');

    // Test if return target data.
    $raw_rows = $this->connection->query('SELECT [name] FROM {test} ORDER BY [name]')->fetchAll();
    $raw_rows = array_slice($raw_rows, 1, 3);
    $this->assertEquals($range_rows, $raw_rows);
  }

}
