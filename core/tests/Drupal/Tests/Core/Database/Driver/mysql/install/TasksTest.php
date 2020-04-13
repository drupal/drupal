<?php

namespace Drupal\Tests\Core\Database\Driver\mysql\install;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\Driver\mysql\Install\Tasks;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MySQL install tasks.
 *
 * @coversDefaultClass \Drupal\Core\Database\Driver\mysql\Install\Tasks
 * @group Database
 */
class TasksTest extends UnitTestCase {

  /**
   * A connection object prophecy.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection|\Prophecy\Prophecy\ObjectProphecy
   */
  private $connection;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->connection = $this->prophesize(Connection::class);
  }

  /**
   * Creates a Tasks object for testing.
   *
   * @return \Drupal\Core\Database\Driver\mysql\Install\Tasks
   */
  private function createTasks(): Tasks {
    /** @var \Drupal\Core\Database\Driver\mysql\Connection $connection */
    $connection = $this->connection->reveal();

    return new class($connection) extends Tasks {

      private $connection;

      public function __construct(Connection $connection) {
        $this->connection = $connection;
      }

      protected function isConnectionActive() {
        return TRUE;
      }

      protected function getConnection() {
        return $this->connection;
      }

      protected function t($string, array $args = [], array $options = []) {
        return $string;
      }

    };
  }

  /**
   * Creates a Tasks object for testing, without connection.
   *
   * @return \Drupal\Core\Database\Driver\mysql\Install\Tasks
   */
  private function createTasksNoConnection(): Tasks {
    return new class() extends Tasks {

      protected function isConnectionActive() {
        return FALSE;
      }

      protected function getConnection() {
        return NULL;
      }

      protected function t($string, array $args = [], array $options = []) {
        return $string;
      }

    };
  }

  /**
   * @covers ::minimumVersion
   * @covers ::name
   * @dataProvider providerNameAndMinimumVersion
   */
  public function testNameAndMinimumVersion(bool $is_mariadb, string $expected_name, string $expected_minimum_version): void {
    $this->connection
      ->isMariaDb()
      ->shouldBeCalledTimes(2)
      ->willReturn($is_mariadb);
    $tasks = $this->createTasks();

    $minimum_version = $tasks->minimumVersion();
    $name = $tasks->name();

    $this->assertSame($expected_minimum_version, $minimum_version);
    $this->assertSame($expected_name, $name);

  }

  /**
   * Provides test data.
   *
   * @return array
   */
  public function providerNameAndMinimumVersion(): array {
    return [
      [
        TRUE,
        'MariaDB',
        Tasks::MARIADB_MINIMUM_VERSION,
      ],
      [
        FALSE,
        'MySQL, Percona Server, or equivalent',
        Tasks::MYSQL_MINIMUM_VERSION,
      ],
    ];
  }

  /**
   * @covers ::name
   */
  public function testNameWithNoConnection() {
    $tasks = $this->createTasksNoConnection();
    $this->assertSame('MySQL, MariaDB, Percona Server, or equivalent', $tasks->name());
  }

}
