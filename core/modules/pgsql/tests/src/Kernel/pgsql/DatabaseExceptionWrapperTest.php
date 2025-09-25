<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests exceptions thrown by queries.
 */
#[Group('Database')]
class DatabaseExceptionWrapperTest extends DriverSpecificKernelTestBase {

  /**
   * Tests Connection::prepareStatement exception on execution.
   */
  public function testPrepareStatementFailOnExecution(): void {
    $this->expectException(\PDOException::class);
    $stmt = $this->connection->prepareStatement('bananas', []);
    $stmt->execute();
  }

}
