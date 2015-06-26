<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\InfoAlterTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the effectiveness of hook_system_info_alter().
 *
 * @group system
 */
class InfoAlterTest extends KernelTestBase {

  public static $modules = array('system');

  /**
   * Tests that theme .info.yml data is rebuild after enabling a module.
   *
   * Tests that info data is rebuilt after a module that implements
   * hook_system_info_alter() is enabled. Also tests if core *_list() functions
   * return freshly altered info.
   */
  function testSystemInfoAlter() {
    \Drupal::state()->set('module_required_test.hook_system_info_alter', TRUE);
    $info = system_rebuild_module_data();
    $this->assertFalse(isset($info['node']->info['required']), 'Before the module_required_test is installed the node module is not required.');

    // Enable the test module.
    \Drupal::service('module_installer')->install(array('module_required_test'), FALSE);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_required_test'), 'Test required module is enabled.');

    $info = system_rebuild_module_data();
    $this->assertTrue($info['node']->info['required'], 'After the module_required_test is installed the node module is required.');
  }
}
