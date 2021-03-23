<?php

namespace Drupal\FunctionalJavascriptTests;

/**
 * Tests that Drupal.throwError can be suppressed to allow a test to pass.
 *
 * @group javascript
 */
class JavascriptErrorsSuppressionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['js_errors_test'];

  /**
   * {@inheritdoc}
   */
  protected $failOnJavascriptConsoleErrors = FALSE;

  /**
   * Tests that Javascript console errors can be suppressed in core.
   */
  public function testJavascriptErrors() {
    // Visit page that will throw a Javascript console error.
    $this->drupalGet('js_errors_test');
    // Ensure that errors from previous page loads will be
    // detected.
    $this->drupalGet('user');
  }

}
