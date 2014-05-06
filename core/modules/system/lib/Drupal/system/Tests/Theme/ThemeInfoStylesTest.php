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

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$base/base-add.css')]")), "$base/base-add.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$base/base-override.css')]")), "$base/base-override.css found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-remove.css')]")), "base-remove.css not found");

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/sub-add.css')]")), "$sub/sub-add.css found");

    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/sub-override.css')]")), "$sub/sub-override.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/base-add.sub-override.css')]")), "$sub/base-add.sub-override.css found");
    $this->assertIdentical(1, count($this->xpath("//link[contains(@href, '$sub/base-remove.sub-override.css')]")), "$sub/base-remove.sub-override.css found");

    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'sub-remove.css')]")), "sub-remove.css not found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-add.sub-remove.css')]")), "base-add.sub-remove.css not found");
    $this->assertIdentical(0, count($this->xpath("//link[contains(@href, 'base-override.sub-remove.css')]")), "base-override.sub-remove.css not found");
  }

}
