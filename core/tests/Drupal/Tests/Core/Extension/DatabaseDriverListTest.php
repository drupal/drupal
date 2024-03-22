<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Database\Database;
use Drupal\Tests\UnitTestCase;

/**
 * Tests DatabaseDriverList methods.
 *
 * @coversDefaultClass \Drupal\Core\Extension\DatabaseDriverList
 * @group extension
 */
class DatabaseDriverListTest extends UnitTestCase {

  /**
   * @covers ::get
   *
   * @dataProvider providerDatabaseDrivers
   */
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
      ['DrivertestMysql', 'driver_test', 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysql'],
      ['DrivertestPgsql', 'driver_test', 'Drupal\\driver_test\\Driver\\Database\\DrivertestPgsql'],
      ['DrivertestMysqlDeprecatedVersion', 'driver_test', 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysqlDeprecatedVersion'],
    ];
  }

}
