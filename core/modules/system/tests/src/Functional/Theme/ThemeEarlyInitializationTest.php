<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the theme system can be correctly initialized early in the page
 * request.
 *
 * @group Theme
 */
class ThemeEarlyInitializationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests that the theme system can generate output in a request listener.
   */
  public function testRequestListener() {
    $this->drupalGet('theme-test/request-listener');
    // Verify that themed output generated in the request listener appears.
    $this->assertRaw('Themed output generated in a KernelEvents::REQUEST listener');
    // Verify that the default theme's CSS still appears even though the theme
    // system was initialized early.
    $this->assertRaw('classy/css/components/action-links.css');
  }

}
