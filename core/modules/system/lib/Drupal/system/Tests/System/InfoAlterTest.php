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
   * Tests that {system}.info is rebuilt after a module that implements
   * hook_system_info_alter() is enabled. Also tests if core *_list() functions
   * return freshly altered info.
   */
  function testSystemInfoAlter() {
    // Enable our test module. Flush all caches, which we assert is the only
    // thing necessary to use the rebuilt {system}.info.
    module_enable(array('module_test'), FALSE);
    $this->resetAll();
    $this->assertTrue(module_exists('module_test'), t('Test module is enabled.'));

    $info = $this->getSystemInfo('seven', 'theme');
    $this->assertTrue(isset($info['regions']['test_region']), t('Altered theme info was added to {system}.info.'));
    $seven_regions = system_region_list('seven');
    $this->assertTrue(isset($seven_regions['test_region']), t('Altered theme info was returned by system_region_list().'));
    $system_list_themes = system_list('theme');
    $info = $system_list_themes['seven']->info;
    $this->assertTrue(isset($info['regions']['test_region']), t('Altered theme info was returned by system_list().'));
    $list_themes = list_themes();
    $this->assertTrue(isset($list_themes['seven']->info['regions']['test_region']), t('Altered theme info was returned by list_themes().'));

    // Disable the module and verify that {system}.info is rebuilt without it.
    module_disable(array('module_test'), FALSE);
    $this->resetAll();
    $this->assertFalse(module_exists('module_test'), t('Test module is disabled.'));

    $info = $this->getSystemInfo('seven', 'theme');
    $this->assertFalse(isset($info['regions']['test_region']), t('Altered theme info was removed from {system}.info.'));
    $seven_regions = system_region_list('seven');
    $this->assertFalse(isset($seven_regions['test_region']), t('Altered theme info was not returned by system_region_list().'));
    $system_list_themes = system_list('theme');
    $info = $system_list_themes['seven']->info;
    $this->assertFalse(isset($info['regions']['test_region']), t('Altered theme info was not returned by system_list().'));
    $list_themes = list_themes();
    $this->assertFalse(isset($list_themes['seven']->info['regions']['test_region']), t('Altered theme info was not returned by list_themes().'));
  }

  /**
   * Returns the info array as it is stored in {system}.
   *
   * @param $name
   *   The name of the record in {system}.
   * @param $type
   *   The type of record in {system}.
   *
   * @return
   *   Array of info, or FALSE if the record is not found.
   */
  function getSystemInfo($name, $type) {
    $raw_info = db_query("SELECT info FROM {system} WHERE name = :name AND type = :type", array(':name' => $name, ':type' => $type))->fetchField();
    return $raw_info ? unserialize($raw_info) : FALSE;
  }
}
