<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\DatabaseDriverList;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DatabaseDriverList methods.
 */
#[CoversClass(DatabaseDriverList::class)]
#[Group('extension')]
class DatabaseDriverListTest extends UnitTestCase {

  /**
   * Tests get.
   */
  #[DataProvider('providerDatabaseDrivers')]
  public function testGet(string $driverName, string $moduleName, string $driverExtensionName): void {
    $driverExtension = Database::getDriverList()->includeTestDrivers(TRUE)->get($driverExtensionName);
    $this->assertSame($driverExtensionName, $driverExtension->getName());
    $this->assertSame($moduleName, $driverExtension->getModule()->getName());
    $this->assertSame($driverName, $driverExtension->getDriverName());
  }

  /**
   * Data provider for testLegacyGetFromDriverName().
   */
  public static function providerDatabaseDrivers(): array {
    return [
      ['mysql', 'mysql', 'Drupal\\mysql\\Driver\\Database\\mysql'],
      ['pgsql', 'pgsql', 'Drupal\\pgsql\\Driver\\Database\\pgsql'],
      ['sqlite', 'sqlite', 'Drupal\\sqlite\\Driver\\Database\\sqlite'],
      ['DriverTestMysql', 'driver_test', 'Drupal\\driver_test\\Driver\\Database\\DriverTestMysql'],
      ['DriverTestPgsql', 'driver_test', 'Drupal\\driver_test\\Driver\\Database\\DriverTestPgsql'],
      [
        'DriverTestMysqlDeprecatedVersion',
        'driver_test',
        'Drupal\\driver_test\\Driver\\Database\\DriverTestMysqlDeprecatedVersion',
      ],
    ];
  }

}
