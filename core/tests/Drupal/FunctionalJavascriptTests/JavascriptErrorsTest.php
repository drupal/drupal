<?php

namespace Drupal\FunctionalJavascriptTests;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests that Drupal.throwError will cause tests to fail.
 *
 * @group javascript
 */
class JavascriptErrorsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['js_errors_test'];

  /**
   * Tests that Javascript console errors cause a test failure.
   *
   * The actual assert for the error is in ::tearDown().
   */
  public function testJavascriptErrors() {
    // Visit page that will throw a Javascript console error.
    $this->drupalGet('js_errors_test');
    // Ensure that errors from previous page loads will be
    // detected.
    $this->drupalGet('user');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    try {
      // Call parent::tearDown() to ensure that an error will found for the
      // expected Javascript error.
      parent::tearDown();
    }
    catch (ExpectationFailedException $exception) {
      $this->assertStringStartsWith('Javascript errors found.', $exception->getMessage());
      $errors = $exception->getComparisonFailure()->getActual();
      $this->assertCount(1, $errors);
      $this->assertStringStartsWith('Error: A manually thrown error.', $errors[0]);
      return;
    }
    $this->fail('Expected Javascript errors fail.');
  }

}
