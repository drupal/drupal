<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Exception\SchemaTableAlreadyExistsException;
use Drupal\Core\Database\Exception\SchemaTableDoesNotExistException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests the shared schema table exception contracts.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SchemaTableExceptionTest extends DatabaseTestBase {

  /**
   * Tests the guard against creating a table that already exists.
   */
  public function testCreateExistingTable(): void {
    $this->expectException(SchemaTableAlreadyExistsException::class);
    $this->connection->schema()->createTable('test', ['fields' => ['id' => ['type' => 'int']]]);
  }

  /**
   * Tests the guard against altering a table that does not exist.
   */
  public function testRenameMissingTable(): void {
    $this->expectException(SchemaTableDoesNotExistException::class);
    $this->connection->schema()->renameTable('normalization_missing', 'normalization_target');
  }

  /**
   * Tests that native table errors are converted by the driver.
   */
  #[TestWith(['SELECT * FROM {normalization_missing}', SchemaTableDoesNotExistException::class], 'select missing table')]
  #[TestWith(['CREATE TABLE {test} (id INTEGER)', SchemaTableAlreadyExistsException::class], 'create existing table')]
  #[TestWith(['SELECT not_a_column FROM {test}', DatabaseExceptionWrapper::class], 'unknown column')]
  public function testNativeErrors(string $sql, string $expected): void {
    try {
      $this->connection->query($sql);
      $this->fail('The query must fail.');
    }
    catch (DatabaseExceptionWrapper $actual) {
      $this->assertSame($expected, $actual::class);
      $this->assertNotNull($actual->getPrevious());
      $this->assertStringStartsWith($actual->getPrevious()->getMessage(), $actual->getMessage());
      $this->assertNotNull($actual->info);
    }
  }

}
