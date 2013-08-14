<?php

/**
 * @file
 * Contains Drupal\system\Tests\Theme\ThemeInfoStylesTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests processing of theme .info.yml stylesheets.
 */
class ThemeInfoStylesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Theme .info.yml styles',
      'description' => 'Tests processing of theme .info.yml stylesheets.',
      'group' => 'Theme',
    );
  }

  /**
   * Tests stylesheets-override and stylesheets-remove.
   */
  function testStylesheets() {
    theme_enable(array('test_basetheme', 'test_subtheme'));
    \Drupal::config('system.theme')
      ->set('default', 'test_subtheme')
      ->save();

    $base = drupal_get_path('theme', 'test_basetheme');
    // Unlike test_basetheme (and the original module CSS), the subtheme decides
    // to put all of its CSS into a ./css subdirectory. All overrides and
    // removals are expected to be based on a file's basename and should work
    // nevertheless.
    $sub = drupal_get_path('theme', 'test_subtheme') . '/css';

    $this->drupalGet('theme-test/info/stylesheets');

    $this->assertRaw("$base/base-add.css");
    $this->assertRaw("$base/base-override.css");
    $this->assertNoRaw("base-remove.css");

    $this->assertRaw("$sub/sub-add.css");

    $this->assertRaw("$sub/sub-override.css");
    $this->assertRaw("$sub/base-add.sub-override.css");
    $this->assertRaw("$sub/base-remove.sub-override.css");

    $this->assertNoRaw("sub-remove.css");
    $this->assertNoRaw("base-add.sub-remove.css");
    $this->assertNoRaw("base-override.sub-remove.css");
  }
}
