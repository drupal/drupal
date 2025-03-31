<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Unit;

use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests MySQL database connections.
 *
 * @coversDefaultClass \Drupal\mysql\Driver\Database\mysql\Connection
 * @group Database
 */
class ConnectionTest extends UnitTestCase {

  /**
   * A mocked MySql connection.
   *
   * @var \Drupal\mysql\Driver\Database\mysql\Connection&\PHPUnit\Framework\MockObject\MockObject
   */
  private Connection&MockObject $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = $this->getMockBuilder(Connection::class)
      ->setConstructorArgs([$this->createMock(\PDO::class), []])
      ->onlyMethods(['getServerVersion'])
      ->getMock();
  }

  /**
   * @covers ::version
   * @covers ::isMariaDb
   * @dataProvider providerVersionAndIsMariaDb
   */
  public function testVersionAndIsMariaDb(bool $expected_is_mariadb, string $server_version, string $expected_version): void {
    $this->connection
      ->method('getServerVersion')
      ->willReturn($server_version);

    $is_mariadb = $this->connection->isMariaDb();
    $version = $this->connection->version();

    $this->assertSame($expected_is_mariadb, $is_mariadb);
    $this->assertSame($expected_version, $version);
  }

  /**
   * Provides test data.
   *
   * @return array
   *   An array of test data.
   */
  public static function providerVersionAndIsMariaDb(): array {
    return [
      // MariaDB.
      [
        TRUE,
        '10.2.0-MariaDB',
        '10.2.0-MariaDB',
      ],
      [
        TRUE,
        '10.2.1-MARIADB',
        '10.2.1-MARIADB',
      ],
      [
        TRUE,
        '10.2.2-alphaX-MARIADB',
        '10.2.2-alphaX-MARIADB',
      ],
      [
        TRUE,
        '5.5.5-10.2.20-MariaDB-1:10.2.20+maria~bionic',
        '10.2.20-MariaDB-1:10.2.20+maria~bionic',
      ],
      [
        TRUE,
        '5.5.5-10.3.22-MariaDB-0+deb10u1',
        '10.3.22-MariaDB-0+deb10u1',
      ],
      [
        TRUE,
        '5.5.5-10.3.22-buzz+-MariaDB-0+deb10u1',
        '10.3.22-buzz+-MariaDB-0+deb10u1',
      ],
      // MySQL.
      [
        FALSE,
        '5.5.5-10.2.20-notMariaDB',
        '5.5.5-10.2.20-notMariaDB',
      ],
      [
        FALSE,
        '5.5.5',
        '5.5.5',
      ],
      [
        FALSE,
        '5.5.5-',
        '5.5.5-',
      ],
      [
        FALSE,
        '5.7.28',
        '5.7.28',
      ],
      [
        FALSE,
        '5.7.28-31',
        '5.7.28-31',
      ],
    ];
  }

}
