<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests exceptions thrown by queries.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class DatabaseExceptionWrapperTest extends KernelTestBase {

  /**
   * Tests the expected database exception thrown for inexistent tables.
   */
  public function testQueryThrowsDatabaseExceptionWrapperException(): void {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection()->query('SELECT * FROM {does_not_exist}');
  }

}
