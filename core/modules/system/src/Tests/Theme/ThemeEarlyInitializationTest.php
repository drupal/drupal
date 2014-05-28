<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\ThemeEarlyInitializationTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for initialization of the theme system early in the request.
 */
class ThemeEarlyInitializationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Early theme initialization',
      'description' => 'Tests that the theme system can be correctly initialized early in the page request.',
      'group' => 'Theme',
    );
  }

  /**
   * Test that the theme system can generate output in a request listener.
   */
  function testRequestListener() {
    $this->drupalGet('theme-test/request-listener');
    // Verify that themed output generated in the request listener appears.
    $this->assertRaw('Themed output generated in a KernelEvents::REQUEST listener');
    // Verify that the default theme's CSS still appears even though the theme
    // system was initialized early.
    $this->assertRaw('stark/css/layout.css');
  }
}
