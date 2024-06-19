<?php

declare(strict_types=1);

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
   * Tests the error handler.
   */
  public function testErrorHandler(): void {
    $config = $this->config('system.logging');
    $error_notice = [
      '%type' => 'Notice',
      '@message' => 'Object of class stdClass could not be converted to int',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];
    $error_warning = [
      '%type' => 'Warning',
      '@message' => 'var_export does not handle circular references',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];
    $error_user_notice = [
      '%type' => 'User warning',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()',
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertSession()->responseContains('<pre class="backtrace">');
    // Ensure we are escaping but not double escaping.
    $this->assertSession()->responseContains('&amp;');
    $this->assertSession()->responseNotContains('&amp;amp;');

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    // Set error reporting to collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // Set error reporting to not collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_SOME)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoErrorMessage($error_notice);
    $this->assertErrorMessage($error_warning);
    $this->assertErrorMessage($error_user_notice);
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // Set error reporting to not show any errors.
    $config->set('error_level', ERROR_REPORTING_HIDE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoErrorMessage($error_notice);
    $this->assertNoErrorMessage($error_warning);
    $this->assertNoErrorMessage($error_user_notice);
    $this->assertNoMessages();
    $this->assertSession()->responseNotContains('<pre class="backtrace">');
  }

  /**
   * Tests a custom error handler set in settings.php.
   */
  public function testCustomErrorHandler(): void {
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\n";
    $settings_php .= "set_error_handler(function() {\n";
    $settings_php .= "  header('HTTP/1.1 418 I\'m a teapot');\n";
    $settings_php .= "  print('Oh oh, flying teapots from a custom error handler');\n";
    $settings_php .= "  exit();\n";
    $settings_php .= "});\n";
    file_put_contents($settings_filename, $settings_php);

    // For most types of errors, PHP throws an \Error object that Drupal
    // catches, so the error handler is not invoked. To test the error handler,
    // generate warnings, which are not thrown/caught.
    $this->drupalGet('error-test/generate-warnings');

    $this->assertSession()->statusCodeEquals(418);
    $this->assertSession()->responseContains('Oh oh, flying teapots from a custom error handler');
  }

  /**
   * Tests the exception handler.
   */
  public function testExceptionHandler(): void {
    $error_exception = [
      '%type' => 'Exception',
      '@message' => 'Drupal & awesome',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerException()',
      '%line' => 56,
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];
    $error_pdo_exception = [
      '%type' => 'DatabaseExceptionWrapper',
      '@message' => 'SELECT "b".* FROM {bananas_are_awesome} "b"',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerPDOException()',
      '%line' => 64,
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];
    $error_renderer_exception = [
      '%type' => 'Exception',
      '@message' => 'This is an exception that occurs during rendering',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
      '%line' => 82,
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];

    $this->drupalGet('error-test/trigger-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertErrorMessage($error_exception);

    $this->drupalGet('error-test/trigger-pdo-exception');
    $this->assertSession()->statusCodeEquals(500);
    // We cannot use assertErrorMessage() since the exact error reported
    // varies from database to database. Check that the SQL string is displayed.
    $this->assertSession()->pageTextContains($error_pdo_exception['%type']);
    // Assert statement improved since static queries adds table alias in the
    // error message.
    $this->assertSession()->pageTextContains($error_pdo_exception['@message']);
    $error_details = new FormattableMarkup('in %function (line ', $error_pdo_exception);
    $this->assertSession()->responseContains($error_details);
    $this->drupalGet('error-test/trigger-renderer-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertErrorMessage($error_renderer_exception);

    // Disable error reporting, ensure that 5xx responses are not cached.
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();

    $this->drupalGet('error-test/trigger-exception');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderNotContains('Cache-Control', 'public');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertNoErrorMessage($error_exception);
  }

  /**
   * Helper function: assert that the error message is found.
   *
   * @internal
   */
  public function assertErrorMessage(array $error): void {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertSession()->responseContains($message);
  }

  /**
   * Helper function: assert that the error message is not found.
   *
   * @internal
   */
  public function assertNoErrorMessage(array $error): void {
    $message = new FormattableMarkup('%type: @message in %function (line ', $error);
    $this->assertSession()->responseNotContains($message);
  }

  /**
   * Asserts that no messages are printed onto the page.
   *
   * Ensures that no messages div exists, which proves that no messages were
   * generated by the error handler, not even an empty one.
   *
   * @internal
   */
  protected function assertNoMessages(): void {
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "messages")]');
  }

}
