<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Bootstrap;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests kernel panic when things are really messed up.
 *
 * @group system
 * @group #slow
 */
class UncaughtExceptionTest extends BrowserTestBase {

  /**
   * Exceptions thrown by site under test that contain this text are ignored.
   *
   * @var string
   */
  protected $expectedExceptionMessage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['error_service_test', 'error_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\ninclude_once 'core/tests/Drupal/FunctionalTests/Bootstrap/ErrorContainer.php';\n";
    $settings_php .= "\ninclude_once 'core/tests/Drupal/FunctionalTests/Bootstrap/ExceptionContainer.php';\n";
    // Ensure we can test errors rather than being caught in
    // \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware.
    $settings_php .= "\ndefine('SIMPLETEST_COLLECT_ERRORS', FALSE);\n";
    file_put_contents($settings_filename, $settings_php);

    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_VERBOSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests uncaught exception handling when system is in a bad state.
   */
  public function testUncaughtException(): void {
    $this->expectedExceptionMessage = 'Oh oh, bananas in the instruments.';
    \Drupal::state()->set('error_service_test.break_bare_html_renderer', TRUE);

    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_HIDE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Try again later.');
    $this->assertSession()->pageTextNotContains($this->expectedExceptionMessage);

    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_ALL,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Try again later.');
    $this->assertSession()->pageTextContains($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests displaying an uncaught fatal error.
   */
  public function testUncaughtFatalError(): void {
    $fatal_error = [
      '%type' => 'TypeError',
      '@message' => 'Drupal\error_test\Controller\ErrorTestController::Drupal\error_test\Controller\{closure}(): Argument #1 ($test) must be of type array, string given, called in ' . \Drupal::root() . '/core/modules/system/tests/modules/error_test/src/Controller/ErrorTestController.php on line 65',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
    ];
    $this->drupalGet('error-test/generate-fatal-errors');
    $this->assertSession()->statusCodeEquals(500);
    $message = new FormattableMarkup('%type: @message in %function (line ', $fatal_error);
    $this->assertSession()->responseContains((string) $message);
    $this->assertSession()->responseContains('<pre class="backtrace">');
    // Ensure we are escaping but not double escaping.
    $this->assertSession()->responseContains('&gt;');
    $this->assertSession()->responseNotContains('&amp;gt;');
  }

  /**
   * Tests uncaught exception handling with custom exception handler.
   */
  public function testUncaughtExceptionCustomExceptionHandler(): void {
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\n";
    $settings_php .= "set_exception_handler(function() {\n";
    $settings_php .= "  header('HTTP/1.1 418 I\'m a teapot');\n";
    $settings_php .= "  print('Oh oh, flying teapots');\n";
    $settings_php .= "});\n";
    file_put_contents($settings_filename, $settings_php);

    \Drupal::state()->set('error_service_test.break_bare_html_renderer', TRUE);

    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(418);
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error. Try again later.');
    $this->assertSession()->pageTextNotContains('Oh oh, bananas in the instruments');
    $this->assertSession()->pageTextContains('Oh oh, flying teapots');
  }

  /**
   * Tests a missing dependency on a service.
   */
  public function testMissingDependency(): void {
    $this->expectedExceptionMessage = 'Too few arguments to function Drupal\error_service_test\LonelyMonkeyClass::__construct(), 0 passed';
    $this->drupalGet('broken-service-class');
    $this->assertSession()->statusCodeEquals(500);

    $this->assertSession()->pageTextContains('The website encountered an unexpected error.');
    $this->assertSession()->pageTextContains($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests a container which has an error.
   */
  public function testErrorContainer(): void {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\FunctionalTests\Bootstrap\ErrorContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    \Drupal::service('kernel')->invalidateContainer();

    $this->expectedExceptionMessage = 'Drupal\FunctionalTests\Bootstrap\ErrorContainer::Drupal\FunctionalTests\Bootstrap\{closure}(): Argument #1 ($container) must be of type Drupal\FunctionalTests\Bootstrap\ErrorContainer';
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);

    $this->assertSession()->pageTextContains($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests a container which has an exception really early.
   */
  public function testExceptionContainer(): void {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\FunctionalTests\Bootstrap\ExceptionContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    \Drupal::service('kernel')->invalidateContainer();

    $this->expectedExceptionMessage = 'Thrown exception during Container::get';
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);

    $this->assertSession()->pageTextContains('The website encountered an unexpected error');
    $this->assertSession()->pageTextContains($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests the case when the database connection is gone.
   */
  public function testLostDatabaseConnection(): void {
    $incorrect_username = $this->randomMachineName(16);
    switch ($this->container->get('database')->driver()) {
      case 'pgsql':
      case 'mysql':
        $this->expectedExceptionMessage = $incorrect_username;
        break;

      default:
        // We can not carry out this test.
        $this->markTestSkipped('Unable to run \Drupal\system\Tests\System\UncaughtExceptionTest::testLostDatabaseConnection for this database type.');
    }

    // We simulate a broken database connection by rewrite settings.php to no
    // longer have the proper data.
    $settings['databases']['default']['default']['username'] = (object) [
      'value' => $incorrect_username,
      'required' => TRUE,
    ];
    $settings['databases']['default']['default']['password'] = (object) [
      'value' => $this->randomMachineName(16),
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('DatabaseAccessDeniedException');
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests fallback to PHP error log when an exception is thrown while logging.
   */
  public function testLoggerException(): void {
    // Ensure the test error log is empty before these tests.
    $this->assertNoErrorsLogged();

    $this->expectedExceptionMessage = 'Deforestation';
    \Drupal::state()->set('error_service_test.break_logger', TRUE);

    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSession()->pageTextContains('The website encountered an unexpected error. Try again later.');
    $this->assertSession()->pageTextContains($this->expectedExceptionMessage);

    // Find fatal error logged to the error.log
    $errors = file(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
    $this->assertCount(10, $errors, 'The error + the error that the logging service is broken has been written to the error log.');
    $this->assertStringContainsString('Failed to log error', $errors[0], 'The error handling logs when an error could not be logged to the logger.');

    $expected_path = \Drupal::root() . '/core/modules/system/tests/modules/error_service_test/src/MonkeysInTheControlRoom.php';
    $expected_line = 67;
    $expected_entry = "Failed to log error: Exception: Deforestation in Drupal\\error_service_test\\MonkeysInTheControlRoom->handle() (line {$expected_line} of {$expected_path})";
    $this->assertStringContainsString($expected_entry, $errors[0], 'Original error logged to the PHP error log when an exception is thrown by a logger');

    // The exception is expected. Do not interpret it as a test failure. Not
    // using File API; a potential error must trigger a PHP warning.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
  }

  /**
   * Asserts that a specific error has been logged to the PHP error log.
   *
   * @param string $error_message
   *   The expected error message.
   *
   * @see \Drupal\Core\Test\FunctionalTestSetupTrait::prepareEnvironment()
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   *
   * @internal
   */
  protected function assertErrorLogged(string $error_message): void {
    $error_log_filename = DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log';
    $this->assertFileExists($error_log_filename);

    $content = file_get_contents($error_log_filename);
    $rows = explode(PHP_EOL, $content);

    // We iterate over the rows in order to be able to remove the logged error
    // afterwards.
    $found = FALSE;
    foreach ($rows as $row_index => $row) {
      if (str_contains($content, $error_message)) {
        $found = TRUE;
        unset($rows[$row_index]);
      }
    }

    file_put_contents($error_log_filename, implode("\n", $rows));

    $this->assertTrue($found, sprintf('The %s error message was logged.', $error_message));
  }

  /**
   * Asserts that no errors have been logged to the PHP error.log thus far.
   *
   * @see \Drupal\Core\Test\FunctionalTestSetupTrait::prepareEnvironment()
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   *
   * @internal
   */
  protected function assertNoErrorsLogged(): void {
    // Since PHP only creates the error.log file when an actual error is
    // triggered, it is sufficient to check whether the file exists.
    $this->assertFileDoesNotExist(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');
  }

}
