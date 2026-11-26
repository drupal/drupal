<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Exception\SchemaTableDoesNotExistException;
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
    try {
      Database::getConnection()->query('SELECT * FROM {does_not_exist} WHERE [not_a_column] = :not_a_value', [':not_a_value' => 'not a value']);
      $this->fail('Query should have failed.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->assertInstanceOf(SchemaTableDoesNotExistException::class, $e);
      $this->assertMatchesRegularExpression('/SELECT \* FROM ".*does_not_exist" WHERE "not_a_column" = :not_a_value/', $e->info->sqlQuery);
      $this->assertSame([':not_a_value' => 'not a value'], $e->info->placeholders);
      $this->assertStringEndsWith(': ' . $e->info->toCompactString(), $e->getMessage());
    }
  }

}
