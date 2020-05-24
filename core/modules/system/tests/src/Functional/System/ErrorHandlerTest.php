<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Performs tests on the Drupal error and exception handler.
 *
 * @group system
 */
class ErrorHandlerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['error_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test the error handler.
   */
  public function testErrorHandler() {
    $config = $this->config('system.logging');
    $error_notice = [
      '%type' => 'Notice',
      '@message' => 'Undefined variable: bananas',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];
    $error_warning = [
      '%type' => 'Warning',
      '@message' => 'Division by zero',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];
    $error_user_notice = [
      '%type' => 'User warning',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertRaw('<pre class="backtrace">', 'Found pre element with backtrace class.');
    // Ensure we are escaping but not double escaping.
    $this->assertRaw('&amp;');
    $this->assertNoRaw('&amp;amp;');

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    // Set error reporting to collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');

    // Set error reporting to not collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_SOME)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');

    // Set error reporting to not show any errors.
    $config->set('error_level', ERROR_REPORTING_HIDE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoErrorMessage($error_notice);
    $this->assertNoErrorMessage($error_warning);
    $this->assertNoErrorMessage($error_user_notice);
    $this->assertNoMessages();
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');
  }

  /**
   * Test the exception handler.
   */
  public function testExceptionHandler() {
    $error_exception = [
      '%type' => 'Exception',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerException()',
      '%line' => 56,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];
    $error_pdo_exception = [
      '%type' => 'DatabaseExceptionWrapper',
      '@message' => 'SELECT * FROM bananas_are_awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerPDOException()',
      '%line' => 64,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];
    $error_renderer_exception = [
      '%type' => 'Exception',
      '@message' => 'This is an exception that occurs during rendering',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
      '%line' => 82,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    ];

    $this->drupalGet('error-test/trigger-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertErrorMessage($error_exception);

    $this->drupalGet('error-test/trigger-pdo-exception');
    $this->assertSession()->statusCodeEquals(500);
    // We cannot use assertErrorMessage() since the exact error reported
    // varies from database to database. Check that the SQL string is displayed.
    $this->assertText($error_pdo_exception['%type'], new FormattableMarkup('Found %type in error page.', $error_pdo_exception));
    $this->assertText($error_pdo_exception['@message'], new FormattableMarkup('Found @message in error page.', $error_pdo_exception));
    $error_details = new FormattableMarkup('in %function (line ', $error_pdo_exception);
    $this->assertRaw($error_details, new FormattableMarkup("Found '@message' in error page.", ['@message' => $error_details]));

    $this->drupalGet('error-test/trigger-renderer-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertErrorMessage($error_renderer_exception);

    // Disable error reporting, ensure that 5xx responses are not cached.
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();

    $this->drupalGet('error-test/trigger-exception');
    $this->assertNull($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertSession()->responseHeaderNotContains('Cache-Control', 'public');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertNoErrorMessage($error_exception);
  }

  /**
   * Helper function: assert that the error message is found.
   */
  public function assertErrorMessage(array $error) {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertRaw($message, new FormattableMarkup('Found error message: @message.', ['@message' => $message]));
  }

  /**
   * Helper function: assert that the error message is not found.
   */
  public function assertNoErrorMessage(array $error) {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertNoRaw($message, new FormattableMarkup('Did not find error message: @message.', ['@message' => $message]));
  }

  /**
   * Asserts that no messages are printed onto the page.
   *
   * @return bool
   *   TRUE, if there are no messages.
   */
  protected function assertNoMessages() {
    return $this->assertEmpty($this->xpath('//div[contains(@class, "messages")]'), 'Ensures that also no messages div exists, which proves that no messages were generated by the error handler, not even an empty one.');
  }

}
