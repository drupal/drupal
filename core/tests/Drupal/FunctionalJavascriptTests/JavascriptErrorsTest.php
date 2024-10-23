<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests that Drupal.throwError will cause a test failure.
 *
 * @group javascript
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
   * Tests that JavaScript console errors will result in a test failure.
   */
  public function testJavascriptErrors(): void {
    // Visit page that will throw a JavaScript console error.
    $this->drupalGet('js_errors_test');
    // Ensure that errors from previous page loads will be
    // detected.
    $this->drupalGet('user');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessageMatches('/^Error: A manually thrown error/');

    // Manually call the method under test, as it cannot be caught by PHPUnit
    // when triggered from assertPostConditions().
    $this->failOnJavaScriptErrors();
  }

  /**
   * Tests JavaScript console errors during asynchronous calls.
   */
  public function testJavascriptErrorsAsync(): void {
    // Visit page that will throw a JavaScript console error in async context.
    $this->drupalGet('js_errors_async_test');
    // Ensure that errors from previous page loads will be detected.
    $this->drupalGet('user');

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessageMatches('/^Error: An error thrown in async context./');

    // Manually call the method under test, as it cannot be caught by PHPUnit
    // when triggered from assertPostConditions().
    $this->failOnJavaScriptErrors();
  }

  /**
   * Clear the JavaScript error log to prevent this test failing for real.
   *
   * @postCondition
   */
  public function clearErrorLog(): void {
    $this->getSession()->executeScript("sessionStorage.removeItem('js_testing_log_test.errors')");
  }

}
