<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group system
 * @group legacy
 */
class SystemDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * @see system_sort_modules_by_info_name()
   */
  public function testSystemSortModulesByInfoName() {
    $module_info = [];
    foreach (\Drupal::service('extension.list.module')->getAllInstalledInfo() as $module => $info) {
      $module_info[$module] = new \stdClass();
      $module_info[$module]->info = $info;
    }
    $this->expectDeprecation('system_sort_modules_by_info_name() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Implement system_sort_by_info_name() instead. See https://www.drupal.org/node/3225624');
    uasort($module_info, 'system_sort_modules_by_info_name');
  }

}
