<?php

/**
 * @file
 * Definition of Drupal\update\Tests\UpdateCoreUnitTest.
 */

namespace Drupal\update\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests update functionality unrelated to the database.
 */
class UpdateCoreUnitTest extends UnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update');

  public static function getInfo() {
    return array(
      'name' => "Unit tests",
      'description' => 'Test update funcionality unrelated to the database.',
      'group' => 'Update',
    );
  }

  function setUp() {
    parent::setUp();
    module_load_include('inc', 'update', 'update.fetch');
  }

  /**
   * Tests that _update_build_fetch_url() builds the URL correctly.
   */
  function testUpdateBuildFetchUrl() {
    //first test that we didn't break the trivial case
    $project['name'] = 'update_test';
    $project['project_type'] = '';
    $project['info']['version'] = '';
    $project['info']['project status url'] = 'http://www.example.com';
    $project['includes'] = array('module1' => 'Module 1', 'module2' => 'Module 2');
    $site_key = '';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . \Drupal::CORE_COMPATIBILITY;
    $url = _update_build_fetch_url($project, $site_key);
    $this->assertEqual($url, $expected, "'$url' when no site_key provided should be '$expected'.");

    //For disabled projects it shouldn't add the site key either.
    $site_key = 'site_key';
    $project['project_type'] = 'disabled';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . \Drupal::CORE_COMPATIBILITY;
    $url = _update_build_fetch_url($project, $site_key);
    $this->assertEqual($url, $expected, "'$url' should be '$expected' for disabled projects.");

    //for enabled projects, adding the site key
    $project['project_type'] = '';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . \Drupal::CORE_COMPATIBILITY;
    $expected .= '?site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');
    $url = _update_build_fetch_url($project, $site_key);
    $this->assertEqual($url, $expected, "When site_key provided, '$url' should be '$expected'.");

    // http://drupal.org/node/1481156 test incorrect logic when URL contains
    // a question mark.
    $project['info']['project status url'] = 'http://www.example.com/?project=';
    $expected = 'http://www.example.com/?project=/' . $project['name'] . '/' . \Drupal::CORE_COMPATIBILITY;
    $expected .= '&site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');
    $url = _update_build_fetch_url($project, $site_key);
    $this->assertEqual($url, $expected, "When ? is present, '$url' should be '$expected'.");

  }
}
