<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\mysql\Driver\Database\mysql\Install\Tasks as MysqlInstallTasks;
use Drupal\Driver\Database\fake\Install\Tasks as FakeInstallTasks;
use Drupal\Driver\Database\CoreFake\Install\Tasks as CustomCoreFakeInstallTasks;
use Drupal\driver_test\Driver\Database\DrivertestMysql\Install\Tasks as DriverTestMysqlInstallTasks;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the db_installer_object() function that is used during installation.
 *
 * These tests run in isolation to prevent the autoloader additions from
 * affecting other tests.
 *
 * @covers ::db_installer_object
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group Database
 * @group legacy
 */
class InstallerObjectTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../../includes/install.inc';
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\Driver\\Database\\fake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/fake");
    $additional_class_loader->addPsr4("Drupal\\Core\\Database\\Driver\\CoreFake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/core/CoreFake");
    $additional_class_loader->addPsr4("Drupal\\Driver\\Database\\CoreFake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/CoreFake");
    $additional_class_loader->addPsr4("Drupal\\driver_test\\Driver\\Database\\DrivertestMysql\\", __DIR__ . "/../../../../../../modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql");
    $additional_class_loader->register(TRUE);
  }

  /**
   * @dataProvider providerDbInstallerObject
   */
  public function testDbInstallerObject($driver, $namespace, $expected_class_name) {
    $this->expectDeprecation('db_installer_object() is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3256641');
    $object = db_installer_object($driver, $namespace);
    $this->assertEquals(get_class($object), $expected_class_name);
  }

  /**
   * Data provider for testDbUrlToConnectionConversion().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - driver: The driver name.
   *   - namespace: The namespace providing the driver.
   *   - class: The fully qualified class name of the expected install task.
   */
  public function providerDbInstallerObject() {
    return [
      // A driver only in the core namespace.
      ['mysql', "Drupal\\mysql\\Driver\\Database\\mysql", MysqlInstallTasks::class],

      // A driver only in the custom namespace.
      // @phpstan-ignore-next-line
      ['fake', "Drupal\\Driver\\Database\\fake", FakeInstallTasks::class],

      // A driver in both namespaces. The custom one takes precedence.
      // @phpstan-ignore-next-line
      ['CoreFake', "Drupal\\Driver\\Database\\CoreFake", CustomCoreFakeInstallTasks::class],

      // A driver from a module that has a different name as the driver.
      ['DrivertestMysql', "Drupal\\driver_test\\Driver\\Database\\DrivertestMysql", DriverTestMysqlInstallTasks::class],
    ];
  }

}
