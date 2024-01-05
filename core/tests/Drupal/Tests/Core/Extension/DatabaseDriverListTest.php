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
   * @covers ::get
   * @group legacy
   *
   * @dataProvider providerDatabaseDrivers
   */
  public function testLegacyGet(string $driverName, string $moduleName, string $driverExtensionName): void {
    $this->expectDeprecation("Passing a database driver name '{$driverName}' to Drupal\\Core\\Extension\\DatabaseDriverList::get() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Pass a database driver namespace instead. See https://www.drupal.org/node/3258175");
    $this->expectDeprecation('Drupal\\Core\\Extension\\DatabaseDriverList::getFromDriverName() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::get() instead, passing a database driver namespace. See https://www.drupal.org/node/3258175');
    $driverExtension = Database::getDriverList()->includeTestDrivers(TRUE)->get($driverName);
    $this->assertSame($driverExtensionName, $driverExtension->getName());
    $this->assertSame($moduleName, $driverExtension->getModule()->getName());
    $this->assertSame($driverName, $driverExtension->getDriverName());
  }

  /**
   * @covers ::getFromDriverName
   * @group legacy
   *
   * @dataProvider providerDatabaseDrivers
   */
  public function testLegacyGetFromDriverName(string $driverName, string $moduleName, string $driverExtensionName): void {
    $this->expectDeprecation('Drupal\\Core\\Extension\\DatabaseDriverList::getFromDriverName() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::get() instead, passing a database driver namespace. See https://www.drupal.org/node/3258175');
    $driverExtension = Database::getDriverList()->includeTestDrivers(TRUE)->getFromDriverName($driverName);
    $this->assertSame($driverExtensionName, $driverExtension->getName());
    $this->assertSame($moduleName, $driverExtension->getModule()->getName());
    $this->assertSame($driverName, $driverExtension->getDriverName());
  }

  /**
   * Data provider for testLegacyGetFromDriverName().
   */
  public function providerDatabaseDrivers(): array {
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
