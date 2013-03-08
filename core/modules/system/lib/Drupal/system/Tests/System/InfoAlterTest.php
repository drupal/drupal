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
    // Enable seven and the test module.
    theme_enable(array('seven'));
    module_enable(array('module_test'), FALSE);
    $this->assertTrue(module_exists('module_test'), 'Test module is enabled.');

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

    // Disable the module and verify that rebuilt .info.yml does not contain it.
    module_disable(array('module_test'), FALSE);
    $this->assertFalse(module_exists('module_test'), 'Test module is disabled.');

    $info = system_get_info('theme', 'seven');
    $this->assertFalse(isset($info['regions']['test_region']), 'Altered theme info was not returned by system_get_info().');
    $seven_regions = system_region_list('seven');
    $this->assertFalse(isset($seven_regions['test_region']), 'Altered theme info was not returned by system_region_list().');
    $system_list_themes = system_list('theme');
    $info = $system_list_themes['seven']->info;
    $this->assertFalse(isset($info['regions']['test_region']), 'Altered theme info was not returned by system_list().');
    $list_themes = list_themes();
    $this->assertFalse(isset($list_themes['seven']->info['regions']['test_region']), 'Altered theme info was not returned by list_themes().');
  }
}
