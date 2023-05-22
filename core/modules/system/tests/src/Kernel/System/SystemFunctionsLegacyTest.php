<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the deprecations in the system module.
 *
 * @group system
 * @group legacy
 */
class SystemFunctionsLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    // Test system_get_module_admin_tasks() require user.permissions service.
    'user',
  ];

  /**
   * @covers ::system_time_zones
   */
  public function testSystemTimeZones() {
    $this->expectDeprecation('system_time_zones() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. This function is no longer used in Drupal core. Use \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsList(), \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsListByRegion() or \DateTimeZone::listIdentifiers() instead. See https://www.drupal.org/node/3023528');
    system_time_zones();
  }

  /**
   * @covers ::system_get_module_admin_tasks
   */
  public function testSystemGetModuleAdminTasksArgument() {
    $module_name = 'System';
    $expected = system_get_module_admin_tasks('system', $module_name);
    $this->expectDeprecation('Calling system_get_module_admin_tasks() with $module_name argument as array is deprecated in drupal:10.2.0 and is required to be string from drupal:11.0.0. Pass only $info["name"] instead. See https://www.drupal.org/node/3357711');
    $this->assertSame($expected, system_get_module_admin_tasks('system', ['name' => $module_name]));
  }

}
