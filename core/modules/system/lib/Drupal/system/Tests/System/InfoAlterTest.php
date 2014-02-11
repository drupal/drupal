<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\InfoAlterTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the effectiveness of hook_system_info_alter().
 */
class InfoAlterTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'System info alter',
      'description' => 'Tests the effectiveness of hook_system_info_alter().',
      'group' => 'System',
    );
  }

  /**
   * Tests that theme .info.yml data is rebuild after enabling a module.
   *
   * Tests that info data is rebuilt after a module that implements
   * hook_system_info_alter() is enabled. Also tests if core *_list() functions
   * return freshly altered info.
   */
  function testSystemInfoAlter() {
    \Drupal::state()->set('module_test.hook_system_info_alter', TRUE);
    $info = system_rebuild_module_data();
    $this->assertFalse(isset($info['node']->info['required']), 'Before the module_test is installed the node module is not required.');
    // Enable seven and the test module.
    theme_enable(array('seven'));
    \Drupal::moduleHandler()->install(array('module_test'), FALSE);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_test'), 'Test module is enabled.');

    // Verify that the rebuilt and altered theme info is returned.
    $info = system_get_info('theme', 'seven');
    $this->assertTrue(isset($info['regions']['test_region']), 'Altered theme info was returned by system_get_info().');
    $seven_regions = system_region_list('seven');
    $this->assertTrue(isset($seven_regions['test_region']), 'Altered theme info was returned by system_region_list().');
    $system_list_themes = system_list('theme');
    $info = $system_list_themes['seven']->info;
    $this->assertTrue(isset($info['regions']['test_region']), 'Altered theme info was returned by system_list().');
    $list_themes = list_themes();
    $this->assertTrue(isset($list_themes['seven']->info['regions']['test_region']), 'Altered theme info was returned by list_themes().');
    system_list_reset();
    $info = system_rebuild_module_data();
    $this->assertTrue($info['node']->info['required'], 'After the module_test is installed the node module is required.');
    \Drupal::state()->set('module_test.hook_system_info_alter', FALSE);
  }
}
