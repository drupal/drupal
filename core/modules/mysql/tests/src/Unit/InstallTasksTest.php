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

    $tasks = $this->getMockBuilder(Tasks::class)
      ->onlyMethods(['getConnection', 't', 'isConnectionActive'])
      ->getMock();
    $tasks->expects($this->once())
      ->method('isConnectionActive')
      ->willReturn(TRUE);
    $tasks->expects($this->exactly(3))
      ->method('getConnection')
      ->willReturn($connection);
    $tasks->expects($this->once())
      ->method('t')
      ->willReturnArgument(0);

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
    $tasks = $this->getMockBuilder(Tasks::class)
      ->onlyMethods(['t', 'isConnectionActive'])
      ->getMock();
    $tasks->expects($this->once())
      ->method('isConnectionActive')
      ->willReturn(FALSE);
    $tasks->expects($this->once())
      ->method('t')
      ->willReturnArgument(0);

    $this->assertSame('MySQL, MariaDB, or equivalent', $tasks->name());
  }

}
