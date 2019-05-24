<?php

namespace Drupal\FunctionalTests\Bootstrap;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests kernel panic when things are really messed up.
 *
 * @group system
 */
class UncaughtExceptionTest extends BrowserTestBase {

  /**
   * Last cURL response.
   *
   * @var string
   */
  protected $response = '';

  /**
   * Last cURL info.
   *
   * @var array
   */
  protected $info = [];

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
  public static $modules = ['error_service_test', 'error_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\ninclude_once 'core/tests/Drupal/FunctionalTests/Bootstrap/ErrorContainer.php';\n";
    $settings_php .= "\ninclude_once 'core/tests/Drupal/FunctionalTests/Bootstrap/ExceptionContainer.php';\n";
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
  public function testUncaughtException() {
    $this->expectedExceptionMessage = 'Oh oh, bananas in the instruments.';
    \Drupal::state()->set('error_service_test.break_bare_html_renderer', TRUE);

    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_HIDE)
      ->save();
    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_HIDE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertResponse(500);
    $this->assertText('The website encountered an unexpected error. Please try again later.');
    $this->assertNoText($this->expectedExceptionMessage);

    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_DISPLAY_ALL)
      ->save();
    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_ALL,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('');
    $this->assertResponse(500);
    $this->assertText('The website encountered an unexpected error. Please try again later.');
    $this->assertText($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests displaying an uncaught fatal error.
   */
  public function testUncaughtFatalError() {
    $fatal_error = [
      '%type' => 'TypeError',
      '@message' => 'Argument 1 passed to Drupal\error_test\Controller\ErrorTestController::Drupal\error_test\Controller\{closure}() must be of the type array, string given, called in ' . \Drupal::root() . '/core/modules/system/tests/modules/error_test/src/Controller/ErrorTestController.php on line 62',
      '%function' => 'Drupal\error_test\Controller\ErrorTestController->Drupal\error_test\Controller\{closure}()',
    ];
    $this->drupalGet('error-test/generate-fatals');
    $this->assertResponse(500, 'Received expected HTTP status code.');
    $message = new FormattableMarkup('%type: @message in %function (line ', $fatal_error);
    $this->assertRaw((string) $message);
    $this->assertRaw('<pre class="backtrace">');
    // Ensure we are escaping but not double escaping.
    $this->assertRaw('&#039;');
    $this->assertNoRaw('&amp;#039;');
  }

  /**
   * Tests uncaught exception handling with custom exception handler.
   */
  public function testUncaughtExceptionCustomExceptionHandler() {
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
    $this->assertResponse(418);
    $this->assertNoText('The website encountered an unexpected error. Please try again later.');
    $this->assertNoText('Oh oh, bananas in the instruments');
    $this->assertText('Oh oh, flying teapots');
  }

  /**
   * Tests a missing dependency on a service.
   */
  public function testMissingDependency() {
    if (version_compare(PHP_VERSION, '7.1') < 0) {
      $this->expectedExceptionMessage = 'Argument 1 passed to Drupal\error_service_test\LonelyMonkeyClass::__construct() must be an instance of Drupal\Core\Database\Connection, non';
    }
    else {
      $this->expectedExceptionMessage = 'Too few arguments to function Drupal\error_service_test\LonelyMonkeyClass::__construct(), 0 passed';
    }
    $this->drupalGet('broken-service-class');
    $this->assertResponse(500);

    $this->assertRaw('The website encountered an unexpected error.');
    $this->assertRaw($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests a missing dependency on a service with a custom error handler.
   */
  public function testMissingDependencyCustomErrorHandler() {
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\n";
    $settings_php .= "set_error_handler(function() {\n";
    $settings_php .= "  header('HTTP/1.1 418 I\'m a teapot');\n";
    $settings_php .= "  print('Oh oh, flying teapots');\n";
    $settings_php .= "  exit();\n";
    $settings_php .= "});\n";
    $settings_php .= "\$settings['teapots'] = TRUE;\n";
    file_put_contents($settings_filename, $settings_php);

    $this->drupalGet('broken-service-class');
    $this->assertResponse(418);
    $this->assertSame('Oh oh, flying teapots', $this->response);
  }

  /**
   * Tests a container which has an error.
   */
  public function testErrorContainer() {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\FunctionalTests\Bootstrap\ErrorContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    \Drupal::service('kernel')->invalidateContainer();

    $this->expectedExceptionMessage = 'Argument 1 passed to Drupal\FunctionalTests\Bootstrap\ErrorContainer::Drupal\FunctionalTests\Bootstrap\{closur';
    $this->drupalGet('');
    $this->assertResponse(500);

    $this->assertRaw($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests a container which has an exception really early.
   */
  public function testExceptionContainer() {
    $settings = [];
    $settings['settings']['container_base_class'] = (object) [
      'value' => '\Drupal\FunctionalTests\Bootstrap\ExceptionContainer',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    \Drupal::service('kernel')->invalidateContainer();

    $this->expectedExceptionMessage = 'Thrown exception during Container::get';
    $this->drupalGet('');
    $this->assertResponse(500);

    $this->assertRaw('The website encountered an unexpected error');
    $this->assertRaw($this->expectedExceptionMessage);
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests the case when the database connection is gone.
   */
  public function testLostDatabaseConnection() {
    $incorrect_username = $this->randomMachineName(16);
    switch ($this->container->get('database')->driver()) {
      case 'pgsql':
      case 'mysql':
        $this->expectedExceptionMessage = $incorrect_username;
        break;
      default:
        // We can not carry out this test.
        $this->pass('Unable to run \Drupal\system\Tests\System\UncaughtExceptionTest::testLostDatabaseConnection for this database type.');
        return;
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
    $this->assertResponse(500);
    $this->assertRaw('DatabaseAccessDeniedException');
    $this->assertErrorLogged($this->expectedExceptionMessage);
  }

  /**
   * Tests fallback to PHP error log when an exception is thrown while logging.
   */
  public function testLoggerException() {
    // Ensure the test error log is empty before these tests.
    $this->assertNoErrorsLogged();

    $this->expectedExceptionMessage = 'Deforestation';
    \Drupal::state()->set('error_service_test.break_logger', TRUE);

    $this->drupalGet('');
    $this->assertResponse(500);
    $this->assertText('The website encountered an unexpected error. Please try again later.');
    $this->assertRaw($this->expectedExceptionMessage);

    // Find fatal error logged to the error.log
    $errors = file(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
    $this->assertIdentical(count($errors), 8, 'The error + the error that the logging service is broken has been written to the error log.');
    $this->assertTrue(strpos($errors[0], 'Failed to log error') !== FALSE, 'The error handling logs when an error could not be logged to the logger.');

    $expected_path = \Drupal::root() . '/core/modules/system/tests/modules/error_service_test/src/MonkeysInTheControlRoom.php';
    $expected_line = 59;
    $expected_entry = "Failed to log error: Exception: Deforestation in Drupal\\error_service_test\\MonkeysInTheControlRoom->handle() (line ${expected_line} of ${expected_path})";
    $this->assert(strpos($errors[0], $expected_entry) !== FALSE, 'Original error logged to the PHP error log when an exception is thrown by a logger');

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
   * @see \Drupal\simpletest\TestBase::prepareEnvironment()
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   */
  protected function assertErrorLogged($error_message) {
    $error_log_filename = DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log';
    if (!file_exists($error_log_filename)) {
      $this->fail('No error logged yet.');
    }

    $content = file_get_contents($error_log_filename);
    $rows = explode(PHP_EOL, $content);

    // We iterate over the rows in order to be able to remove the logged error
    // afterwards.
    $found = FALSE;
    foreach ($rows as $row_index => $row) {
      if (strpos($content, $error_message) !== FALSE) {
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
   * @see \Drupal\simpletest\TestBase::prepareEnvironment()
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   */
  protected function assertNoErrorsLogged() {
    // Since PHP only creates the error.log file when an actual error is
    // triggered, it is sufficient to check whether the file exists.
    $this->assertFalse(file_exists(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log'), 'PHP error.log is empty.');
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * Executes a cURL request for processing errors and exceptions.
   *
   * @param string|\Drupal\Core\Url $path
   *   Request path.
   * @param array $extra_options
   *   (optional) Curl options to pass to curl_setopt()
   * @param array $headers
   *   (optional) Not used.
   */
  protected function drupalGet($path, array $extra_options = [], array $headers = []) {
    $url = $this->buildUrl($path, ['absolute' => TRUE]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, drupal_generate_test_ua($this->databasePrefix));
    $this->response = curl_exec($ch);
    $this->info = curl_getinfo($ch);
    curl_close($ch);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertResponse($code) {
    $this->assertSame($code, $this->info['http_code']);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertText($text) {
    $this->assertContains($text, $this->response);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNoText($text) {
    $this->assertNotContains($text, $this->response);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertRaw($text) {
    $this->assertText($text);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNoRaw($text) {
    $this->assertNoText($text);
  }

}
