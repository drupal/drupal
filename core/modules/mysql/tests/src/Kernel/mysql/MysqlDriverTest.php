<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the MySQL database driver classes in Core.
 *
 * @group Database
 */
class MysqlDriverTest extends DriverSpecificKernelTestBase {

  /**
   * @covers \Drupal\mysql\Driver\Database\mysql\Connection
   */
  public function testConnection(): void {
    $connection = new Connection($this->createMock(StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

}
