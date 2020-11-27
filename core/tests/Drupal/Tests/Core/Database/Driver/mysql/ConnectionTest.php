<?php

namespace Drupal\Tests\Core\Database\Driver\mysql;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Tests\UnitTestCase;

/**
 * Tests MySQL database connections.
 *
 * @coversDefaultClass \Drupal\Core\Database\Driver\mysql\Connection
 * @group Database
 * @runTestsInSeparateProcesses
 */
class ConnectionTest extends UnitTestCase {

  /**
   * A PDO statement prophecy.
   *
   * @var \PDOStatement|\Prophecy\Prophecy\ObjectProphecy
   */
  private $pdoStatement;

  /**
   * A PDO object prophecy.
   *
   * @var \PDO|\Prophecy\Prophecy\ObjectProphecy
   */
  private $pdoConnection;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->pdoStatement = $this->prophesize(\PDOStatement::class);
    $this->pdoConnection = $this->prophesize(\PDO::class);
  }

  /**
   * Creates a Connection object for testing.
   *
   * @return \Drupal\Core\Database\Driver\mysql\Connection
   */
  private function createConnection(): Connection {
    /** @var \PDO $pdo_connection */
    $pdo_connection = $this->pdoConnection->reveal();

    return new class($pdo_connection) extends Connection {

      public function __construct(\PDO $connection) {
        $this->connection = $connection;
      }

    };
  }

  /**
   * @covers ::version
   * @covers ::isMariaDb
   * @dataProvider providerVersionAndIsMariaDb
   */
  public function testVersionAndIsMariaDb(bool $expected_is_mariadb, string $server_version, string $expected_version): void {
    $this->pdoStatement
      ->fetchColumn()
      ->shouldBeCalled()
      ->willReturn($server_version);

    $this->pdoConnection
      ->query('SELECT VERSION()')
      ->shouldBeCalled()
      ->willReturn($this->pdoStatement->reveal());

    $connection = $this->createConnection();

    $is_mariadb = $connection->isMariaDb();
    $version = $connection->version();

    $this->assertSame($expected_is_mariadb, $is_mariadb);
    $this->assertSame($expected_version, $version);
  }

  /**
   * Provides test data.
   *
   * @return array
   */
  public function providerVersionAndIsMariaDb(): array {
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
