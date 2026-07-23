<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Unit;

use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\mysql\Driver\Database\mysql\Install\Tasks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MySQL install tasks.
 */
#[CoversClass(Tasks::class)]
#[Group('Database')]
class InstallTasksTest extends UnitTestCase {

  /**
   * Creates a Tasks object for testing.
   *
   * @param \Drupal\mysql\Driver\Database\mysql\Connection $connection
   *   The database connection.
   *
   * @return \Drupal\mysql\Driver\Database\mysql\Install\Tasks
   *   A Tasks object.
   */
  private function createTasks(Connection $connection): Tasks {
    return new class($connection) extends Tasks {

      /**
       * The database connection.
       */
      private Connection $connection;

      public function __construct(Connection $connection) {
        $this->connection = $connection;
      }

      /**
       * {@inheritdoc}
       */
      protected function isConnectionActive(): bool {
        return TRUE;
      }

      /**
       * {@inheritdoc}
       */
      protected function getConnection(): Connection {
        return $this->connection;
      }

      /**
       * {@inheritdoc}
       */
      protected function t($string, array $args = [], array $options = []) {
        return $string;
      }

    };
  }

  /**
   * Creates a Tasks object for testing, without connection.
   *
   * @return \Drupal\mysql\Driver\Database\mysql\Install\Tasks
   *   A Tasks object.
   */
  private function createTasksNoConnection(): Tasks {
    return new class() extends Tasks {

      /**
       * {@inheritdoc}
       */
      protected function isConnectionActive(): bool {
        return FALSE;
      }

      /**
       * {@inheritdoc}
       */
      protected function getConnection(): null {
        return NULL;
      }

      /**
       * {@inheritdoc}
       */
      protected function t($string, array $args = [], array $options = []) {
        return $string;
      }

    };
  }

  /**
   * Tests name and minimum version.
   *
   * @legacy-covers ::minimumVersion
   * @legacy-covers ::name
   */
  #[DataProvider('providerNameAndMinimumVersion')]
  public function testNameAndMinimumVersion(bool $is_mariadb, string $expected_name, string $expected_minimum_version): void {
    $connection = $this->createMock(Connection::class);
    $connection
      ->expects($this->exactly(2))
      ->method('isMariaDb')
      ->willReturn($is_mariadb);
    $tasks = $this->createTasks($connection);

    $minimum_version = $tasks->minimumVersion();
    $name = $tasks->name();

    $this->assertSame($expected_minimum_version, $minimum_version);
    $this->assertSame($expected_name, $name);

  }

  /**
   * Provides test data.
   *
   * @return array
   *   An array of test data.
   */
  public static function providerNameAndMinimumVersion(): array {
    return [
      [
        TRUE,
        'MariaDB',
        Tasks::MARIADB_MINIMUM_VERSION,
      ],
      [
        FALSE,
        'MySQL or equivalent',
        Tasks::MYSQL_MINIMUM_VERSION,
      ],
    ];
  }

  /**
   * Tests name with no connection.
   *
   * @legacy-covers ::name
   */
  public function testNameWithNoConnection(): void {
    $tasks = $this->createTasksNoConnection();
    $this->assertSame('MySQL, MariaDB, or equivalent', $tasks->name());
  }

}
