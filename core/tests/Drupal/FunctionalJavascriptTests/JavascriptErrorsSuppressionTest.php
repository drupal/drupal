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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['js_errors_test'];

  /**
   * {@inheritdoc}
   */
  protected $failOnJavascriptConsoleErrors = FALSE;

  /**
   * Tests that JavaScript console errors can be suppressed.
   */
  public function testJavascriptErrors(): void {
    // Visit page that will throw a JavaScript console error.
    $this->drupalGet('js_errors_test');
    // Ensure that errors from previous page loads will be
    // detected.
    $this->drupalGet('user');
  }

}
