<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Pdo\Mysql;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the deprecations of the MySQL database driver classes in Core.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class MysqlDriverTest extends DriverSpecificKernelTestBase {

  /**
   * Tests connection.
   *
   * @legacy-covers \Drupal\mysql\Driver\Database\mysql\Connection
   */
  public function testConnection(): void {
    // @phpstan-ignore class.notFound
    $connection = new Connection($this->createMock(\PHP_VERSION_ID >= 80400 ? Mysql::class : StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

}
