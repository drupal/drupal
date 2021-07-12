<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group system
 * @group legacy
 */
class SystemModuleAdminTasksDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * @see system_get_module_admin_tasks()
   * @see drupal_static_reset()
   */
  public function testSystemGetModuleAdminTasksDeprecation(): void {
    $this->expectDeprecation("system_get_module_admin_tasks() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the 'system.module_admin_tasks_helper' service with the getModuleAdminTasks() method instead. See https://www.drupal.org/node/3038972");
    system_get_module_admin_tasks('foo', []);
    $this->expectDeprecation("Calling drupal_static_reset() with 'system_get_module_admin_tasks' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3038972");
    drupal_static_reset('system_get_module_admin_tasks');
  }

}
