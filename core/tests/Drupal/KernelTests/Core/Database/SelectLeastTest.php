<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the SQL LEAST operator.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SelectLeastTest extends DatabaseTestBase {

  /**
   * Tests the SQL LEAST operator.
   */
  #[DataProvider('selectLeastProvider')]
  public function testSelectLeast($values, $expected): void {
    $least = $this->connection->query("SELECT LEAST(:values[])", [':values[]' => $values])->fetchField();
    $this->assertEquals($expected, $least);
  }

  /**
   * Provides data for testing the LEAST operator.
   */
  public static function selectLeastProvider(): array {
    return [
      [[1, 2, 3, 4, 5, 6], 1],
      [['A', 'B', 'C', 'NULL', 'F'], 'A'],
      [['NULL', 'NULL'], 'NULL'],
      [['TRUE', 'FALSE'], 'FALSE'],
      [['A', 'B', 'C', 'NULL'], 'A'],
    ];
  }

}
