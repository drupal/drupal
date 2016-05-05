<?php

namespace Drupal\system\Tests\System;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Performs tests on the Drupal error and exception handler.
 *
 * @group system
 */
class ErrorHandlerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('error_test');

  /**
   * Test the error handler.
   */
  function testErrorHandler() {
    $config = $this->config('system.logging');
    $error_notice = array(
      '%type' => 'Notice',
      '@message' => 'Undefined variable: bananas',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_warning = array(
      '%type' => 'Warning',
      '@message' => 'Division by zero',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_user_notice = array(
      '%type' => 'User warning',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $fatal_error = array(
      '%type' => 'Recoverable fatal error',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
      '@message' => 'Argument 1 passed to Drupal\error_test\Controller\ErrorTestController::Drupal\error_test\Controller\{closure}() must be of the type array, string given, called in ' . \Drupal::root() . '/core/modules/system/tests/modules/error_test/src/Controller/ErrorTestController.php on line 62 and defined',
    );
    if (version_compare(PHP_VERSION, '7.0.0-dev') >= 0) {
      // In PHP 7, instead of a recoverable fatal error we get a TypeError.
      $fatal_error['%type'] = 'TypeError';
      // The error message also changes in PHP 7.
      $fatal_error['@message'] = 'Argument 1 passed to Drupal\error_test\Controller\ErrorTestController::Drupal\error_test\Controller\{closure}() must be of the type array, string given, called in ' . \Drupal::root() . '/core/modules/system/tests/modules/error_test/src/Controller/ErrorTestController.php on line 62';
    }

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertRaw('<pre class="backtrace">', 'Found pre element with backtrace class.');
    // Ensure we are escaping but not double escaping.
    $this->assertRaw('&amp;');
    $this->assertNoRaw('&amp;amp;');

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-fatals');
    $this->assertResponse(500, 'Received expected HTTP status code.');
    $this->assertErrorMessage($fatal_error);
    $this->assertRaw('<pre class="backtrace">', 'Found pre element with backtrace class.');
    // Ensure we are escaping but not double escaping.
    $this->assertRaw('&#039;');
    $this->assertNoRaw('&amp;#039;');

    // Remove the recoverable fatal error from the assertions, it's wanted here.
    // Ensure that we just remove this one recoverable fatal error (in PHP 7 this
    // is a TypeError).
    foreach ($this->assertions as $key => $assertion) {
      if (in_array($assertion['message_group'], ['Recoverable fatal error', 'TypeError']) && strpos($assertion['message'], 'Argument 1 passed to Drupal\error_test\Controller\ErrorTestController::Drupal\error_test\Controller\{closure}() must be of the type array, string given, called in') !== FALSE) {
        unset($this->assertions[$key]);
        $this->deleteAssert($assertion['message_id']);
      }
    }
    // Drop the single exception.
    $this->results['#exception']--;

    // Set error reporting to collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');
    $this->assertErrorLogged($fatal_error['@message']);

    // Set error reporting to not collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_SOME)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertNoErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');

    // Set error reporting to not show any errors.
    $config->set('error_level', ERROR_REPORTING_HIDE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertNoErrorMessage($error_notice);
    $this->assertNoErrorMessage($error_warning);
    $this->assertNoErrorMessage($error_user_notice);
    $this->assertNoMessages();
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');
  }

  /**
   * Test the exception handler.
   */
  function testExceptionHandler() {
    // Ensure the test error log is empty before these tests.
    $this->assertNoErrorsLogged();

    $error_exception = array(
      '%type' => 'Exception',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerException()',
      '%line' => 56,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_pdo_exception = array(
      '%type' => 'DatabaseExceptionWrapper',
      '@message' => 'SELECT * FROM bananas_are_awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerPDOException()',
      '%line' => 64,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_renderer_exception = array(
      '%type' => 'Exception',
      '@message' => 'This is an exception that occurs during rendering',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
      '%line' => 82,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );

    $this->drupalGet('error-test/trigger-exception');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    $this->assertErrorMessage($error_exception);

    $this->drupalGet('error-test/trigger-pdo-exception');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    // We cannot use assertErrorMessage() since the exact error reported
    // varies from database to database. Check that the SQL string is displayed.
    $this->assertText($error_pdo_exception['%type'], format_string('Found %type in error page.', $error_pdo_exception));
    $this->assertText($error_pdo_exception['@message'], format_string('Found @message in error page.', $error_pdo_exception));
    $error_details = format_string('in %function (line ', $error_pdo_exception);
    $this->assertRaw($error_details, format_string("Found '@message' in error page.", array('@message' => $error_details)));

    $this->drupalGet('error-test/trigger-renderer-exception');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    $this->assertErrorMessage($error_renderer_exception);

    // Disable error reporting, ensure that 5xx responses are not cached.
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();

    $this->drupalGet('error-test/trigger-exception');
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE, 'Received expected HTTP status line.');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    $this->assertNoErrorMessage($error_exception);

    // The exceptions are expected. Do not interpret them as a test failure.
    // Not using File API; a potential error must trigger a PHP warning.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Helper function: assert that the error message is found.
   */
  function assertErrorMessage(array $error) {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertRaw($message, format_string('Found error message: @message.', array('@message' => $message)));
  }

  /**
   * Helper function: assert that the error message is not found.
   */
  function assertNoErrorMessage(array $error) {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertNoRaw($message, format_string('Did not find error message: @message.', array('@message' => $message)));
  }

  /**
   * Asserts that no messages are printed onto the page.
   *
   * @return bool
   *   TRUE, if there are no messages.
   */
  protected function assertNoMessages() {
    return $this->assertFalse($this->xpath('//div[contains(@class, "messages")]'), 'Ensures that also no messages div exists, which proves that no messages were generated by the error handler, not even an empty one.');
  }

}
