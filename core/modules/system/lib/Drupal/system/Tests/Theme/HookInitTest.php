<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\HookInitTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for initialization of the theme system in hook_init().
 */
class HookInitTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme initialization in hook_init()',
      'description' => 'Tests that the theme system can be correctly initialized in hook_init().',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp('theme_test');
  }

  /**
   * Test that the theme system can generate output when called by hook_init().
   */
  function testThemeInitializationHookInit() {
    $this->drupalGet('theme-test/hook-init');
    // Verify that themed output generated in hook_init() appears.
    $this->assertRaw('Themed output generated in hook_init()');
    // Verify that the default theme's CSS still appears when the theme system
    // is initialized in hook_init().
    $this->assertRaw('stark/css/layout.css');
  }
}
