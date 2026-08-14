<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the PostgreSQL implementation of truncate queries.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class TruncateTest extends DriverSpecificDatabaseTestBase {

  /**
   * Tests that the SQL is a TRUNCATE statement, also inside a transaction.
   *
   * TRUNCATE is transactional on PostgreSQL, so unlike the parent
   * implementation there is no fallback to a DELETE statement when the
   * query runs inside a transaction.
   */
  public function testTruncateSqlStatement(): void {
    $this->assertFalse($this->connection->inTransaction());
    $this->assertStringContainsString('TRUNCATE {test}', (string) $this->connection->truncate('test'));

    $transaction = $this->connection->startTransaction();
    $this->assertTrue($this->connection->inTransaction());
    $sql = (string) $this->connection->truncate('test');
    $this->assertStringContainsString('TRUNCATE {test}', $sql);
    $this->assertStringNotContainsString('DELETE', $sql);
    $transaction->rollBack();
  }

}
