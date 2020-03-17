<?php

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Driver\mysql\Install\Tasks as MysqlInstallTasks;
use Drupal\Driver\Database\fake\Install\Tasks as FakeInstallTasks;
use Drupal\Driver\Database\corefake\Install\Tasks as CustomCoreFakeInstallTasks;
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
 */
class InstallerObjectTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    require_once __DIR__ . '/../../../../../includes/install.inc';
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\Driver\\Database\\fake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/fake");
    $additional_class_loader->addPsr4("Drupal\\Core\\Database\\Driver\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/core/corefake");
    $additional_class_loader->addPsr4("Drupal\\Driver\\Database\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/custom/corefake");
    $additional_class_loader->register(TRUE);
  }

  /**
   * @dataProvider providerDbInstallerObject
   */
  public function testDbInstallerObject($driver, $expected_class_name) {
    $object = db_installer_object($driver);
    $this->assertEquals(get_class($object), $expected_class_name);
  }

  /**
   * Dataprovider for testDbUrltoConnectionConversion().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - driver: The driver name.
   *   - class: The fully qualified class name of the expected install task.
   */
  public function providerDbInstallerObject() {
    return [
      // A driver only in the core namespace.
      ['mysql', MysqlInstallTasks::class],

      // A driver only in the custom namespace.
      ['fake', FakeInstallTasks::class],

      // A driver in both namespaces. The custom one takes precedence.
      ['corefake', CustomCoreFakeInstallTasks::class],
    ];
  }

}
