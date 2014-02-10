<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\ErrorHandlerTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests error and exception handlers.
 */
class ErrorHandlerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('error_test');

  public static function getInfo() {
    return array(
      'name' => 'Error handlers',
      'description' => 'Performs tests on the Drupal error and exception handler.',
      'group' => 'System',
    );
  }

  /**
   * Test the error handler.
   */
  function testErrorHandler() {
    $config = \Drupal::config('system.logging');
    $error_notice = array(
      '%type' => 'Notice',
      '!message' => 'Undefined variable: bananas',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_warning = array(
      '%type' => 'Warning',
      '!message' => 'Division by zero',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_user_notice = array(
      '%type' => 'User warning',
      '!message' => 'Drupal is awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );

    // Set error reporting to display verbose notices.
    \Drupal::config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertRaw('<pre class="backtrace">', 'Found pre element with backtrace class.');

    // Set error reporting to collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertResponse(200, 'Received expected HTTP status code.');
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertNoRaw('<pre class="backtrace">', 'Did not find pre element with backtrace class.');

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
      '!message' => 'Drupal is awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerException()',
      '%line' => 56,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );
    $error_pdo_exception = array(
      '%type' => 'DatabaseExceptionWrapper',
      '!message' => 'SELECT * FROM bananas_are_awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerPDOException()',
      '%line' => 64,
      '%file' => drupal_get_path('module', 'error_test') . '/error_test.module',
    );

    $this->drupalGet('error-test/trigger-exception');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    $this->assertErrorMessage($error_exception);

    $this->drupalGet('error-test/trigger-pdo-exception');
    $this->assertTrue(strpos($this->drupalGetHeader(':status'), '500 Service unavailable (with message)'), 'Received expected HTTP status line.');
    // We cannot use assertErrorMessage() since the extact error reported
    // varies from database to database. Check that the SQL string is displayed.
    $this->assertText($error_pdo_exception['%type'], format_string('Found %type in error page.', $error_pdo_exception));
    $this->assertText($error_pdo_exception['!message'], format_string('Found !message in error page.', $error_pdo_exception));
    $error_details = format_string('in %function (line ', $error_pdo_exception);
    $this->assertRaw($error_details, format_string("Found '!message' in error page.", array('!message' => $error_details)));

    // The exceptions are expected. Do not interpret them as a test failure.
    // Not using File API; a potential error must trigger a PHP warning.
    unlink(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Helper function: assert that the error message is found.
   */
  function assertErrorMessage(array $error) {
    $message = t('%type: !message in %function (line ', $error);
    $this->assertRaw($message, format_string('Found error message: !message.', array('!message' => $message)));
  }

  /**
   * Helper function: assert that the error message is not found.
   */
  function assertNoErrorMessage(array $error) {
    $message = t('%type: !message in %function (line ', $error);
    $this->assertNoRaw($message, format_string('Did not find error message: !message.', array('!message' => $message)));
  }
}
