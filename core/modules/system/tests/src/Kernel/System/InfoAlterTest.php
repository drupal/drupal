<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the effectiveness of hook_system_info_alter().
 *
 * @group system
 */
class InfoAlterTest extends KernelTestBase {

  protected static $modules = ['system'];

  /**
   * Tests that theme .info.yml data is rebuild after enabling a module.
   *
   * Tests that info data is rebuilt after a module that implements
   * hook_system_info_alter() is enabled. Also tests if core *_list() functions
   * return freshly altered info.
   */
  public function testSystemInfoAlter() {
    \Drupal::state()->set('module_required_test.hook_system_info_alter', TRUE);
    $info = \Drupal::service('extension.list.module')->getList();
    $this->assertFalse(isset($info['node']->info['required']), 'Before the module_required_test is installed the node module is not required.');

    // Enable the test module.
    \Drupal::service('module_installer')->install(['module_required_test'], FALSE);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_required_test'), 'Test required module is enabled.');

    $info = \Drupal::service('extension.list.module')->getList();
    $this->assertTrue($info['node']->info['required'], 'After the module_required_test is installed the node module is required.');
  }

}
