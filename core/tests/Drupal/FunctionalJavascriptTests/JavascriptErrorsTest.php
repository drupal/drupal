<?php

namespace Drupal\FunctionalJavascriptTests;

/**
 * Tests that Drupal.throwError will cause a deprecation warning.
 *
 * @group javascript
 * @group legacy
 */
class JavascriptErrorsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['js_errors_test'];

  /**
   * Tests that JavaScript console errors will result in a deprecation warning.
   */
  public function testJavascriptErrors(): void {
    $this->expectDeprecation('Not failing JavaScript test for JavaScript errors is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. This test had the following JavaScript errors: Error: A manually thrown error.');
    // Visit page that will throw a JavaScript console error.
    $this->drupalGet('js_errors_test');
    // Ensure that errors from previous page loads will be
    // detected.
    $this->drupalGet('user');
  }

}
