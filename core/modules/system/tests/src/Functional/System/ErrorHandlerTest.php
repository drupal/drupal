<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Performs tests on the Drupal error and exception handler.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
class ErrorHandlerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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
    $error_notice = '<em class="placeholder">Notice</em>: Object of class stdClass could not be converted to int in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;generateWarnings()</em> (line';
    $error_warning = '<em class="placeholder">Warning</em>: var_export does not handle circular references in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;generateWarnings()</em> (line';
    $error_user_notice = '<em class="placeholder">User warning</em>: Drupal &amp; awesome in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;generateWarnings()</em> (line';

    // Set error reporting to display verbose notices.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($error_notice);
    $this->assertSession()->responseContains($error_warning);
    $this->assertSession()->responseContains($error_user_notice);
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
    $this->assertSession()->responseContains($error_notice);
    $this->assertSession()->responseContains($error_warning);
    $this->assertSession()->responseContains($error_user_notice);
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // Set error reporting to not collect notices.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_SOME)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($error_notice);
    $this->assertSession()->responseContains($error_warning);
    $this->assertSession()->responseContains($error_user_notice);
    $this->assertSession()->responseNotContains('<pre class="backtrace">');

    // Set error reporting to not show any errors.
    $config->set('error_level', ERROR_REPORTING_HIDE)->save();
    $this->drupalGet('error-test/generate-warnings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($error_notice);
    $this->assertSession()->responseNotContains($error_warning);
    $this->assertSession()->responseNotContains($error_user_notice);
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
    $error_exception = '<em class="placeholder">Exception</em>: Drupal &amp; awesome in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;triggerException()</em> (line';
    $select = \Drupal::database()->select('bananas_are_awesome', 'b')->fields('b');
    $message = \Drupal::database()->prepareStatement((string) $select, [])->getQueryString();
    $message = str_replace(["\r", "\n"], ' ', $message);
    $error_pdo_exception = [
      '%type' => 'DatabaseExceptionWrapper',
      '@message' => $message,
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->triggerPDOException()',
      '%line' => 64,
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];
    $error_renderer_exception = '<em class="placeholder">Exception</em>: This is an exception that occurs during rendering in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;{closure:Drupal\error_test\Controller\ErrorTestController::triggerRendererException():104}()</em> (line';
    $this->drupalGet('error-test/trigger-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->responseContains($error_exception);

    $this->drupalGet('error-test/trigger-pdo-exception');
    $this->assertSession()->statusCodeEquals(500);
    // We cannot use assertSession()->responseContains() since the exact error reported
    // varies from database to database. Check that the SQL string is displayed.
    $this->assertSession()->pageTextContains($error_pdo_exception['%type']);
    // Assert statement improved since static queries adds table alias in the
    // error message.
    $this->assertSession()->pageTextContains($error_pdo_exception['@message']);
    $error_details = 'in <em class="placeholder">Drupal\error_test\Controller\ErrorTestController-&gt;triggerPDOException()</em> (line';
    $this->assertSession()->responseContains($error_details);
    $this->drupalGet('error-test/trigger-renderer-exception');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->responseContains($error_renderer_exception);

    // Disable error reporting, ensure that 5xx responses are not cached.
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();

    $this->drupalGet('error-test/trigger-exception');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'UNCACHEABLE (no cacheability)');
    $this->assertSession()->responseHeaderNotContains('Cache-Control', 'public');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->responseNotContains($error_exception);
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
