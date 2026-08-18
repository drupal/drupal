<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Unit;

use Drupal\pgsql\Driver\Database\pgsql\Connection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Pdo\Pgsql;

/**
 * Tests Drupal\pgsql\Driver\Database\pgsql\Connection.
 */
#[CoversClass(Connection::class)]
#[Group('Database')]
class ConnectionTest extends UnitTestCase {

  /**
   * Tests that no queries are executed when opening a connection.
   *
   * The client encoding is set through the DSN in ::open(), as part of the
   * connection startup packet, so constructing the connection must not send
   * any statement (an extra round trip) to the database.
   */
  public function testNoQueriesOnConnect(): void {
    $pdo = $this->createMock(Pgsql::class);
    $pdo->expects($this->never())->method('exec');
    $pdo->expects($this->never())->method('query');
    $pdo->expects($this->never())->method('prepare');

    new Connection($pdo, []);
  }

  /**
   * Tests that init_commands are executed when opening a connection.
   */
  public function testInitCommandsOnConnect(): void {
    $pdo = $this->createMock(Pgsql::class);
    $pdo->expects($this->once())
      ->method('exec')
      ->with("SET timezone = 'UTC'");

    new Connection($pdo, [
      'init_commands' => [
        "SET timezone = 'UTC'",
      ],
    ]);
  }

}
