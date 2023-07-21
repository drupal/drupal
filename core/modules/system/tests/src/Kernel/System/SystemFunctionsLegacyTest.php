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
   * @covers ::system_retrieve_file
   */
  public function testSystemRetrieveFile() {
    $this->expectDeprecation('system_retrieve_file is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3223362');
    $retrieved_file = system_retrieve_file('http://example.com/foo.txt');
    $this->assertFalse($retrieved_file);
  }

  /**
   * Tests system_get_module_admin_tasks() deprecation.
   *
   * @see system_get_module_admin_tasks()
   * @see drupal_static_reset()
   */
  public function testSystemGetModuleAdminTasksDeprecation(): void {
    $this->expectDeprecation("system_get_module_admin_tasks() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the 'system.module_admin_links_helper' service with the getModuleAdminLinks() method and 'user.module_permissions_link_helper' service with the ::getModulePermissionsLink() method instead. See https://www.drupal.org/node/3038972");
    system_get_module_admin_tasks('foo', 'Foo');
    $this->expectDeprecation("Calling drupal_static_reset() with 'system_get_module_admin_tasks' as an argument is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3038972");
    drupal_static_reset('system_get_module_admin_tasks');
  }

}
