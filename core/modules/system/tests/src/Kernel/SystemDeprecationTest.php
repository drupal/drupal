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
    $module_info = \Drupal::service('extension.list.module')->getAllInstalledInfo();
    $to_sort = [
      'user' => (object) ['info' => $module_info['user']],
      'system' => (object) ['info' => $module_info['system']],
    ];

    $this->expectDeprecation('system_sort_modules_by_info_name() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionList::sortByName() instead. See https://www.drupal.org/node/3225999');
    uasort($to_sort, 'system_sort_modules_by_info_name');
    $this->assertSame(['system', 'user'], array_keys($to_sort));
  }

}
