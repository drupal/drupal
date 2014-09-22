<?php

/**
 * @file
 * Definition of \Drupal\simpletest\TestBase.
 */

namespace Drupal\simpletest;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Random;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Base class for Drupal tests.
 *
 * Do not extend this class directly; use either
 * \Drupal\simpletest\WebTestBase or \Drupal\simpletest\KernelTestBase.
 */
abstract class TestBase {
  /**
   * The test run ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The site directory of this test run.
   *
   * @var string
   */
  protected $siteDirectory = NULL;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix = NULL;

  /**
   * The site directory of the original parent site.
   *
   * @var string
   */
  protected $originalSite;

  /**
   * The original file directory, before it was changed for testing purposes.
   *
   * @var string
   */
  protected $originalFileDirectory = NULL;

  /**
   * Time limit for the test.
   */
  protected $timeLimit = 500;

  /**
   * Current results of this test case.
   *
   * @var Array
   */
  public $results = array(
    '#pass' => 0,
    '#fail' => 0,
    '#exception' => 0,
    '#debug' => 0,
  );

  /**
   * Assertions thrown in that test case.
   *
   * @var Array
   */
  protected $assertions = array();

  /**
   * This class is skipped when looking for the source of an assertion.
   *
   * When displaying which function an assert comes from, it's not too useful
   * to see "WebTestBase->drupalLogin()', we would like to see the test
   * that called it. So we need to skip the classes defining these helper
   * methods.
   */
  protected $skipClasses = array(__CLASS__ => TRUE);

  /**
   * TRUE if verbose debugging is enabled.
   *
   * @var boolean
   */
  public $verbose;

  /**
   * Incrementing identifier for verbose output filenames.
   *
   * @var integer
   */
  protected $verboseId = 0;

  /**
   * Safe class name for use in verbose output filenames.
   *
   * Namespaces separator (\) replaced with _.
   *
   * @var string
   */
  protected $verboseClassName;

  /**
   * Directory where verbose output files are put.
   *
   * @var string
   */
  protected $verboseDirectory;

  /**
   * The original database prefix when running inside Simpletest.
   *
   * @var string
   */
  protected $originalPrefix;

  /**
   * URL to the verbose output file directory.
   *
   * @var string
   */
  protected $verboseDirectoryUrl;

  /**
   * The settings array.
   */
  protected $originalSettings;

  /**
   * The public file directory for the test environment.
   *
   * This is set in TestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $public_files_directory;

  /**
   * Whether to die in case any test assertion fails.
   *
   * @var boolean
   *
   * @see run-tests.sh
   */
  public $dieOnFail = FALSE;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * The dependency injection container used in the test.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The config importer that can used in a test.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * The random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * The name of the session cookie.
   */
  protected $originalSessionName;

  /**
   * Constructor for Test.
   *
   * @param $test_id
   *   Tests with the same id are reported together.
   */
  public function __construct($test_id = NULL) {
    $this->testId = $test_id;
  }

  /**
   * Performs setup tasks before each individual test method is run.
   */
  abstract protected function setUp();

  /**
   * Checks the matching requirements for Test.
   *
   * @return
   *   Array of errors containing a list of unmet requirements.
   */
  protected function checkRequirements() {
    return array();
  }

  /**
   * Internal helper: stores the assert.
   *
   * @param $status
   *   Can be 'pass', 'fail', 'exception', 'debug'.
   *   TRUE is a synonym for 'pass', FALSE for 'fail'.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   * @param $caller
   *   By default, the assert comes from a function whose name starts with
   *   'test'. Instead, you can specify where this assert originates from
   *   by passing in an associative array as $caller. Key 'file' is
   *   the name of the source file, 'line' is the line number and 'function'
   *   is the caller function itself.
   */
  protected function assert($status, $message = '', $group = 'Other', array $caller = NULL) {
    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    // Increment summary result counter.
    $this->results['#' . $status]++;

    // Get the function information about the call to the assertion method.
    if (!$caller) {
      $caller = $this->getAssertionCall();
    }

    // Creation assertion array that can be displayed while tests are running.
    $this->assertions[] = $assertion = array(
      'test_id' => $this->testId,
      'test_class' => get_class($this),
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    );

    // Store assertion for display after the test has completed.
    self::getDatabaseConnection()
      ->insert('simpletest')
      ->fields($assertion)
      ->execute();

    // We do not use a ternary operator here to allow a breakpoint on
    // test failure.
    if ($status == 'pass') {
      return TRUE;
    }
    else {
      if ($this->dieOnFail && ($status == 'fail' || $status == 'exception')) {
        exit(1);
      }
      return FALSE;
    }
  }

  /**
   * Store an assertion from outside the testing context.
   *
   * This is useful for inserting assertions that can only be recorded after
   * the test case has been destroyed, such as PHP fatal errors. The caller
   * information is not automatically gathered since the caller is most likely
   * inserting the assertion on behalf of other code. In all other respects
   * the method behaves just like \Drupal\simpletest\TestBase::assert() in terms
   * of storing the assertion.
   *
   * @return
   *   Message ID of the stored assertion.
   *
   * @see \Drupal\simpletest\TestBase::assert()
   * @see \Drupal\simpletest\TestBase::deleteAssert()
   */
  public static function insertAssert($test_id, $test_class, $status, $message = '', $group = 'Other', array $caller = array()) {
    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    $caller += array(
      'function' => 'Unknown',
      'line' => 0,
      'file' => 'Unknown',
    );

    $assertion = array(
      'test_id' => $test_id,
      'test_class' => $test_class,
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    );

    return self::getDatabaseConnection()
      ->insert('simpletest')
      ->fields($assertion)
      ->execute();
  }

  /**
   * Delete an assertion record by message ID.
   *
   * @param $message_id
   *   Message ID of the assertion to delete.
   *
   * @return
   *   TRUE if the assertion was deleted, FALSE otherwise.
   *
   * @see \Drupal\simpletest\TestBase::insertAssert()
   */
  public static function deleteAssert($message_id) {
    return (bool) self::getDatabaseConnection()
      ->delete('simpletest')
      ->condition('message_id', $message_id)
      ->execute();
  }

  /**
   * Returns the database connection to the site running Simpletest.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to use for inserting assertions.
   */
  public static function getDatabaseConnection() {
    // Check whether there is a test runner connection.
    // @see run-tests.sh
    // @todo Convert Simpletest UI runner to create + use this connection, too.
    try {
      $connection = Database::getConnection('default', 'test-runner');
    }
    catch (ConnectionNotDefinedException $e) {
      // Check whether there is a backup of the original default connection.
      // @see TestBase::prepareEnvironment()
      try {
        $connection = Database::getConnection('default', 'simpletest_original_default');
      }
      catch (ConnectionNotDefinedException $e) {
        // If TestBase::prepareEnvironment() or TestBase::restoreEnvironment()
        // failed, the test-specific database connection does not exist
        // yet/anymore, so fall back to the default of the (UI) test runner.
        $connection = Database::getConnection('default', 'default');
      }
    }
    return $connection;
  }

  /**
   * Cycles through backtrace until the first non-assertion method is found.
   *
   * @return
   *   Array representing the true caller.
   */
  protected function getAssertionCall() {
    $backtrace = debug_backtrace();

    // The first element is the call. The second element is the caller.
    // We skip calls that occurred in one of the methods of our base classes
    // or in an assertion function.
   while (($caller = $backtrace[1]) &&
         ((isset($caller['class']) && isset($this->skipClasses[$caller['class']])) ||
           substr($caller['function'], 0, 6) == 'assert')) {
      // We remove that call.
      array_shift($backtrace);
    }

    return Error::getLastCaller($backtrace);
  }

  /**
   * Check to see if a value is not false.
   *
   * False values are: empty string, 0, NULL, and FALSE.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertTrue($value, $message = '', $group = 'Other') {
    return $this->assert((bool) $value, $message ? $message : String::format('Value @value is TRUE.', array('@value' => var_export($value, TRUE))), $group);
  }

  /**
   * Check to see if a value is false.
   *
   * False values are: empty string, 0, NULL, and FALSE.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertFalse($value, $message = '', $group = 'Other') {
    return $this->assert(!$value, $message ? $message : String::format('Value @value is FALSE.', array('@value' => var_export($value, TRUE))), $group);
  }

  /**
   * Check to see if a value is NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNull($value, $message = '', $group = 'Other') {
    return $this->assert(!isset($value), $message ? $message : String::format('Value @value is NULL.', array('@value' => var_export($value, TRUE))), $group);
  }

  /**
   * Check to see if a value is not NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotNull($value, $message = '', $group = 'Other') {
    return $this->assert(isset($value), $message ? $message : String::format('Value @value is not NULL.', array('@value' => var_export($value, TRUE))), $group);
  }

  /**
   * Check to see if two values are equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertEqual($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first == $second, $message ? $message : String::format('Value @first is equal to value @second.', array('@first' => var_export($first, TRUE), '@second' => var_export($second, TRUE))), $group);
  }

  /**
   * Check to see if two values are not equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotEqual($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first != $second, $message ? $message : String::format('Value @first is not equal to value @second.', array('@first' => var_export($first, TRUE), '@second' => var_export($second, TRUE))), $group);
  }

  /**
   * Check to see if two values are identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first === $second, $message ? $message : String::format('Value @first is identical to value @second.', array('@first' => var_export($first, TRUE), '@second' => var_export($second, TRUE))), $group);
  }

  /**
   * Check to see if two values are not identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->assert($first !== $second, $message ? $message : String::format('Value @first is not identical to value @second.', array('@first' => var_export($first, TRUE), '@second' => var_export($second, TRUE))), $group);
  }

  /**
   * Checks to see if two objects are identical.
   *
   * @param object $object1
   *   The first object to check.
   * @param object $object2
   *   The second object to check.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertIdenticalObject($object1, $object2, $message = '', $group = 'Other') {
    $message = $message ?: String::format('!object1 is identical to !object2', array(
      '!object1' => var_export($object1, TRUE),
      '!object2' => var_export($object2, TRUE),
    ));
    $identical = TRUE;
    foreach ($object1 as $key => $value) {
      $identical = $identical && isset($object2->$key) && $object2->$key === $value;
    }
    return $this->assertTrue($identical, $message, $group);
  }

  /**
   * Asserts that no errors have been logged to the PHP error.log thus far.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   *
   * @see TestBase::prepareEnvironment()
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   */
  protected function assertNoErrorsLogged() {
    // Since PHP only creates the error.log file when an actual error is
    // triggered, it is sufficient to check whether the file exists.
    return $this->assertFalse(file_exists(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log'), 'PHP error.log is empty.');
  }

  /**
   * Fire an assertion that is always positive.
   *
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE.
   */
  protected function pass($message = NULL, $group = 'Other') {
    return $this->assert(TRUE, $message, $group);
  }

  /**
   * Fire an assertion that is always negative.
   *
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   FALSE.
   */
  protected function fail($message = NULL, $group = 'Other') {
    return $this->assert(FALSE, $message, $group);
  }

  /**
   * Fire an error assertion.
   *
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\String::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   * @param $caller
   *   The caller of the error.
   *
   * @return
   *   FALSE.
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    if ($group == 'User notice') {
      // Since 'User notice' is set by trigger_error() which is used for debug
      // set the message to a status of 'debug'.
      return $this->assert('debug', $message, 'Debug', $caller);
    }

    return $this->assert('exception', $message, $group, $caller);
  }

  /**
   * Logs a verbose message in a text file.
   *
   * The link to the verbose message will be placed in the test results as a
   * passing assertion with the text '[verbose message]'.
   *
   * @param $message
   *   The verbose message to be stored.
   *
   * @see simpletest_verbose()
   */
  protected function verbose($message) {
    // Do nothing if verbose debugging is disabled.
    if (!$this->verbose) {
      return;
    }

    $message = '<hr />ID #' . $this->verboseId . ' (<a href="' . $this->verboseClassName . '-' . ($this->verboseId - 1) . '.html">Previous</a> | <a href="' . $this->verboseClassName . '-' . ($this->verboseId + 1) . '.html">Next</a>)<hr />' . $message;
    $verbose_filename = $this->verboseDirectory . '/' . $this->verboseClassName . '-' . $this->verboseId . '.html';
    if (file_put_contents($verbose_filename, $message, FILE_APPEND)) {
      $url = $this->verboseDirectoryUrl . '/' . $this->verboseClassName . '-' . $this->verboseId . '.html';
      // Not using l() to avoid invoking the theme system, so that unit tests
      // can use verbose() as well.
      $url = '<a href="' . $url . '" target="_blank">Verbose message</a>';
      $this->error($url, 'User notice');
    }
    $this->verboseId++;
  }

  /**
   * Run all tests in this class.
   *
   * Regardless of whether $methods are passed or not, only method names
   * starting with "test" are executed.
   *
   * @param $methods
   *   (optional) A list of method names in the test case class to run; e.g.,
   *   array('testFoo', 'testBar'). By default, all methods of the class are
   *   taken into account, but it can be useful to only run a few selected test
   *   methods during debugging.
   */
  public function run(array $methods = array()) {
    $class = get_class($this);

    if ($missing_requirements = $this->checkRequirements()) {
      $object_info = new \ReflectionObject($this);
      $caller = array(
        'file' => $object_info->getFileName(),
      );
      foreach ($missing_requirements as $missing_requirement) {
        TestBase::insertAssert($this->testId, $class, FALSE, $missing_requirement, 'Requirements check', $caller);
      }
      return;
    }

    TestServiceProvider::$currentTest = $this;
    $simpletest_config = \Drupal::config('simpletest.settings');

    // Unless preset from run-tests.sh, retrieve the current verbose setting.
    if (!isset($this->verbose)) {
      $this->verbose = $simpletest_config->get('verbose');
    }
    if ($this->verbose) {
      // Initialize verbose debugging.
      $this->verbose = TRUE;
      $this->verboseDirectory = PublicStream::basePath() . '/simpletest/verbose';
      $this->verboseDirectoryUrl = file_create_url($this->verboseDirectory);
      if (file_prepare_directory($this->verboseDirectory, FILE_CREATE_DIRECTORY) && !file_exists($this->verboseDirectory . '/.htaccess')) {
        file_put_contents($this->verboseDirectory . '/.htaccess', "<IfModule mod_expires.c>\nExpiresActive Off\n</IfModule>\n");
      }
      $this->verboseClassName = str_replace("\\", "_", $class);
    }
    // HTTP auth settings (<username>:<password>) for the simpletest browser
    // when sending requests to the test site.
    $this->httpauth_method = (int) $simpletest_config->get('httpauth.method');
    $username = $simpletest_config->get('httpauth.username');
    $password = $simpletest_config->get('httpauth.password');
    if (!empty($username) && !empty($password)) {
      $this->httpauth_credentials = $username . ':' . $password;
    }

    set_error_handler(array($this, 'errorHandler'));
    // Iterate through all the methods in this class, unless a specific list of
    // methods to run was passed.
    $test_methods = array_filter(get_class_methods($class), function ($method) {
      return strpos($method, 'test') === 0;
    });
    if (empty($test_methods)) {
      // Call $this->assert() here because we need to pass along custom caller
      // information, lest the wrong originating code file/line be identified.
      $this->assert(FALSE, 'No test methods found.', 'Requirements', array('function' => __METHOD__ . '()', 'file' => __FILE__, 'line' => __LINE__));
    }
    if ($methods) {
      $test_methods = array_intersect($test_methods, $methods);
    }
    foreach ($test_methods as $method) {
      // Insert a fail record. This will be deleted on completion to ensure
      // that testing completed.
      $method_info = new \ReflectionMethod($class, $method);
      $caller = array(
        'file' => $method_info->getFileName(),
        'line' => $method_info->getStartLine(),
        'function' => $class . '->' . $method . '()',
      );
      $test_completion_check_id = TestBase::insertAssert($this->testId, $class, FALSE, 'The test did not complete due to a fatal error.', 'Completion check', $caller);

      try {
        $this->prepareEnvironment();
      }
      catch (\Exception $e) {
        $this->exceptionHandler($e);
        // The prepareEnvironment() method isolates the test from the parent
        // Drupal site by creating a random database prefix and test site
        // directory. If this fails, a test would possibly operate in the
        // parent site. Therefore, the entire test run for this test class
        // has to be aborted.
        // restoreEnvironment() cannot be called, because we do not know
        // where exactly the environment setup failed.
        break;
      }

      try {
        $this->setUp();
      }
      catch (\Exception $e) {
        $this->exceptionHandler($e);
        // Abort if setUp() fails, since all test methods will fail.
        // But ensure to clean up and restore the environment, since
        // prepareEnvironment() succeeded.
        $this->restoreEnvironment();
        break;
      }
      try {
        $this->$method();
      }
      catch (\Exception $e) {
        $this->exceptionHandler($e);
      }
      try {
        $this->tearDown();
      }
      catch (\Exception $e) {
        $this->exceptionHandler($e);
        // If a test fails to tear down, abort the entire test class, since
        // it is likely that all tests will fail in the same way and a
        // failure here only results in additional test artifacts that have
        // to be manually deleted.
        $this->restoreEnvironment();
        break;
      }

      $this->restoreEnvironment();
      // Remove the test method completion check record.
      TestBase::deleteAssert($test_completion_check_id);
    }

    TestServiceProvider::$currentTest = NULL;
    // Clear out the error messages and restore error handler.
    drupal_get_messages();
    restore_error_handler();
  }

  /**
   * Generates a database prefix for running tests.
   *
   * The database prefix is used by prepareEnvironment() to setup a public files
   * directory for the test to be run, which also contains the PHP error log,
   * which is written to in case of a fatal error. Since that directory is based
   * on the database prefix, all tests (even unit tests) need to have one, in
   * order to access and read the error log.
   *
   * @see TestBase::prepareEnvironment()
   *
   * The generated database table prefix is used for the Drupal installation
   * being performed for the test. It is also used as user agent HTTP header
   * value by the cURL-based browser of DrupalWebTestCase, which is sent to the
   * Drupal installation of the test. During early Drupal bootstrap, the user
   * agent HTTP header is parsed, and if it matches, all database queries use
   * the database table prefix that has been generated here.
   *
   * @see WebTestBase::curlInitialize()
   * @see drupal_valid_test_ua()
   */
  private function prepareDatabasePrefix() {
    // Ensure that the generated test site directory does not exist already,
    // which may happen with a large amount of concurrent threads and
    // long-running tests.
    do {
      $suffix = mt_rand(100000, 999999);
      $this->siteDirectory = 'sites/simpletest/' . $suffix;
      $this->databasePrefix = 'simpletest' . $suffix;
    } while (is_dir(DRUPAL_ROOT . '/' . $this->siteDirectory));

    // As soon as the database prefix is set, the test might start to execute.
    // All assertions as well as the SimpleTest batch operations are associated
    // with the testId, so the database prefix has to be associated with it.
    $affected_rows = self::getDatabaseConnection()->update('simpletest_test_id')
      ->fields(array('last_prefix' => $this->databasePrefix))
      ->condition('test_id', $this->testId)
      ->execute();
    if (!$affected_rows) {
      throw new \RuntimeException('Failed to set up database prefix.');
    }
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @see TestBase::prepareEnvironment()
   */
  private function changeDatabasePrefix() {
    if (empty($this->databasePrefix)) {
      $this->prepareDatabasePrefix();
    }
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo('simpletest_original_default')) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_default' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    Database::renameConnection('default', 'simpletest_original_default');
    foreach ($connection_info as $target => $value) {
      // Replace the full table prefix definition to ensure that no table
      // prefixes of the test runner leak into the test.
      $connection_info[$target]['prefix'] = array(
        'default' => $value['prefix']['default'] . $this->databasePrefix,
      );
    }
    Database::addConnectionInfo('default', 'default', $connection_info['default']);
  }

  /**
   * Act on global state information before the environment is altered for a test.
   *
   * Allows e.g. KernelTestBase to prime system/extension info from the
   * parent site (and inject it into the test environment so as to improve
   * performance).
   */
  protected function beforePrepareEnvironment() {
  }

  /**
   * Prepares the current environment for running the test.
   *
   * Backups various current environment variables and resets them, so they do
   * not interfere with the Drupal site installation in which tests are executed
   * and can be restored in TestBase::restoreEnvironment().
   *
   * Also sets up new resources for the testing environment, such as the public
   * filesystem and configuration directories.
   *
   * This method is private as it must only be called once by TestBase::run()
   * (multiple invocations for the same test would have unpredictable
   * consequences) and it must not be callable or overridable by test classes.
   *
   * @see TestBase::beforePrepareEnvironment()
   */
  private function prepareEnvironment() {
    $user = \Drupal::currentUser();
    // Allow (base) test classes to backup global state information.
    $this->beforePrepareEnvironment();

    // Create the database prefix for this test.
    $this->prepareDatabasePrefix();

    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // When running the test runner within a test, back up the original database
    // prefix.
    if (DRUPAL_TEST_IN_CHILD_SITE) {
      $this->originalPrefix = drupal_valid_test_ua();
    }

    // Backup current in-memory configuration.
    $this->originalSite = conf_path();
    $this->originalSettings = Settings::getAll();
    $this->originalConfig = $GLOBALS['config'];
    // @todo Remove all remnants of $GLOBALS['conf'].
    // @see https://drupal.org/node/2183323
    $this->originalConf = isset($GLOBALS['conf']) ? $GLOBALS['conf'] : NULL;

    // Backup statics and globals.
    $this->originalContainer = clone \Drupal::getContainer();
    $this->originalLanguage = $language_interface;
    $this->originalConfigDirectories = $GLOBALS['config_directories'];

    // Save further contextual information.
    // Use the original files directory to avoid nesting it within an existing
    // simpletest directory if a test is executed within a test.
    $this->originalFileDirectory = Settings::get('file_public_path', conf_path() . '/files');
    $this->originalProfile = drupal_get_profile();
    $this->originalUser = isset($user) ? clone $user : NULL;

    // Prevent that session data is leaked into the UI test runner by closing
    // the session and then setting the session-name (i.e. the name of the
    // session cookie) to a random value. If a test starts a new session, then
    // it will be associated with a different session-name. After the test-run
    // it can be safely destroyed.
    // @see TestBase::restoreEnvironment()
    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }
    $this->originalSessionName = session_name();
    session_name('SIMPLETEST' . Crypt::randomBytesBase64());

    // Save and clean the shutdown callbacks array because it is static cached
    // and will be changed by the test run. Otherwise it will contain callbacks
    // from both environments and the testing environment will try to call the
    // handlers defined by the original one.
    $callbacks = &drupal_register_shutdown_function();
    $this->originalShutdownCallbacks = $callbacks;
    $callbacks = array();

    // Create test directory ahead of installation so fatal errors and debug
    // information can be logged during installation process.
    file_prepare_directory($this->siteDirectory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    // Prepare filesystem directory paths.
    $this->public_files_directory = $this->siteDirectory . '/files';
    $this->private_files_directory = $this->siteDirectory . '/private';
    $this->temp_files_directory = $this->siteDirectory . '/temp';
    $this->translation_files_directory = $this->siteDirectory . '/translations';

    $this->generatedTestFiles = FALSE;

    // Ensure the configImporter is refreshed for each test.
    $this->configImporter = NULL;

    // Unregister all custom stream wrappers of the parent site.
    // Availability of Drupal stream wrappers varies by test base class:
    // - KernelTestBase supports and maintains stream wrappers in a custom
    //   way.
    // - WebTestBase re-initializes Drupal stream wrappers after installation.
    // The original stream wrappers are restored after the test run.
    // @see TestBase::restoreEnvironment()
    $wrappers = file_get_stream_wrappers();
    foreach ($wrappers as $scheme => $info) {
      stream_wrapper_unregister($scheme);
    }

    // Reset statics.
    drupal_static_reset();

    // Ensure there is no service container.
    $this->container = NULL;
    \Drupal::setContainer(NULL);

    // Unset globals.
    unset($GLOBALS['config_directories']);
    unset($GLOBALS['config']);
    unset($GLOBALS['conf']);

    // Log fatal errors.
    ini_set('log_errors', 1);
    ini_set('error_log', DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');

    // Change the database prefix.
    $this->changeDatabasePrefix();

    // After preparing the environment and changing the database prefix, we are
    // in a valid test environment.
    drupal_valid_test_ua($this->databasePrefix);
    conf_path(FALSE, TRUE);

    // Reset settings.
    new Settings(array(
      // For performance, simply use the database prefix as hash salt.
      'hash_salt' => $this->databasePrefix,
    ));

    drupal_set_time_limit($this->timeLimit);
  }

  /**
   * Performs cleanup tasks after each individual test method has been run.
   */
  protected function tearDown() {
  }

  /**
   * Cleans up the test environment and restores the original environment.
   *
   * Deletes created files, database tables, and reverts environment changes.
   *
   * This method needs to be invoked for both unit and integration tests.
   *
   * @see TestBase::prepareDatabasePrefix()
   * @see TestBase::changeDatabasePrefix()
   * @see TestBase::prepareEnvironment()
   */
  private function restoreEnvironment() {
    // Destroy the session if one was started during the test-run.
    $_SESSION = array();
    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
      $params = session_get_cookie_params();
      setcookie(session_name(), '', REQUEST_TIME - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_name($this->originalSessionName);

    // Reset all static variables.
    // Unsetting static variables will potentially invoke destruct methods,
    // which might call into functions that prime statics and caches again.
    // In that case, all functions are still operating on the test environment,
    // which means they may need to access its filesystem and database.
    drupal_static_reset();

    if ($this->container && $this->container->has('state') && $state = $this->container->get('state')) {
      $captured_emails = $state->get('system.test_mail_collector') ?: array();
      $emailCount = count($captured_emails);
      if ($emailCount) {
        $message = $emailCount == 1 ? '1 email was sent during this test.' : $emailCount . ' emails were sent during this test.';
        $this->pass($message, 'Email');
      }
    }

    // Sleep for 50ms to allow shutdown functions and terminate events to
    // complete. Further information: https://drupal.org/node/2194357.
    usleep(50000);

    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_default');
    $original_prefix = $original_connection_info['default']['prefix']['default'];
    $test_connection_info = Database::getConnectionInfo('default');
    $test_prefix = $test_connection_info['default']['prefix']['default'];
    if ($original_prefix != $test_prefix) {
      $tables = Database::getConnection()->schema()->findTables($test_prefix . '%');
      $prefix_length = strlen($test_prefix);
      foreach ($tables as $table) {
        if (Database::getConnection()->schema()->dropTable(substr($table, $prefix_length))) {
          unset($tables[$table]);
        }
      }
    }

    // In case a fatal error occurred that was not in the test process read the
    // log to pick up any fatal errors.
    simpletest_log_read($this->testId, $this->databasePrefix, get_class($this));

    // Delete test site directory.
    file_unmanaged_delete_recursive($this->siteDirectory, array($this, 'filePreDeleteCallback'));

    // Restore original database connection.
    Database::removeConnection('default');
    Database::renameConnection('simpletest_original_default', 'default');

    // Reset all static variables.
    // All destructors of statically cached objects have been invoked above;
    // this second reset is guaranteed to reset everything to nothing.
    drupal_static_reset();

    // Restore original in-memory configuration.
    $GLOBALS['config'] = $this->originalConfig;
    $GLOBALS['conf'] = $this->originalConf;
    new Settings($this->originalSettings);

    // Restore original statics and globals.
    \Drupal::setContainer($this->originalContainer);
    $GLOBALS['config_directories'] = $this->originalConfigDirectories;

    if (isset($this->originalPrefix)) {
      drupal_valid_test_ua($this->originalPrefix);
    }
    else {
      drupal_valid_test_ua(FALSE);
    }
    conf_path(TRUE, TRUE);

    // Restore stream wrappers of the test runner.
    file_get_stream_wrappers();

    // Restore original shutdown callbacks.
    $callbacks = &drupal_register_shutdown_function();
    $callbacks = $this->originalShutdownCallbacks;
  }

  /**
   * Handle errors during test runs.
   *
   * Because this is registered in set_error_handler(), it has to be public.
   *
   * @see set_error_handler
   */
  public function errorHandler($severity, $message, $file = NULL, $line = NULL) {
    if ($severity & error_reporting()) {
      require_once DRUPAL_ROOT . '/core/includes/errors.inc';
      $error_map = array(
        E_STRICT => 'Run-time notice',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core error',
        E_CORE_WARNING => 'Core warning',
        E_USER_ERROR => 'User error',
        E_USER_WARNING => 'User warning',
        E_USER_NOTICE => 'User notice',
        E_RECOVERABLE_ERROR => 'Recoverable error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User deprecated',
      );

      $backtrace = debug_backtrace();

      // Add verbose backtrace for errors, but not for debug() messages.
      if ($severity !== E_USER_NOTICE) {
        $verbose_backtrace = $backtrace;
        array_shift($verbose_backtrace);
        $message .= '<pre class="backtrace">' . Error::formatBacktrace($verbose_backtrace) . '</pre>';
      }

      $this->error($message, $error_map[$severity], Error::getLastCaller($backtrace));
    }
    return TRUE;
  }

  /**
   * Handle exceptions.
   *
   * @see set_exception_handler
   */
  protected function exceptionHandler($exception) {
    require_once DRUPAL_ROOT . '/core/includes/errors.inc';
    $backtrace = $exception->getTrace();
    $verbose_backtrace = $backtrace;
    // Push on top of the backtrace the call that generated the exception.
    array_unshift($backtrace, array(
      'line' => $exception->getLine(),
      'file' => $exception->getFile(),
    ));
    // \Drupal\Core\Utility\Error::decodeException() runs the exception
    // message through \Drupal\Component\Utility\String::checkPlain().
    $decoded_exception = Error::decodeException($exception);
    unset($decoded_exception['backtrace']);
    $message = String::format('%type: !message in %function (line %line of %file). <pre class="backtrace">!backtrace</pre>', $decoded_exception + array(
      '!backtrace' => Error::formatBacktrace($verbose_backtrace),
    ));
    $this->error($message, 'Uncaught exception', Error::getLastCaller($backtrace));
  }

  /**
   * Changes in memory settings.
   *
   * @param $name
   *   The name of the setting to return.
   * @param $value
   *   The value of the setting.
   *
   * @see \Drupal\Core\Site\Settings::get()
   */
  protected function settingsSet($name, $value) {
    $settings = Settings::getAll();
    $settings[$name] = $value;
    new Settings($settings);
  }

  /**
   * Generates a pseudo-random string of ASCII characters of codes 32 to 126.
   *
   * Do not use this method when special characters are not possible (e.g., in
   * machine or file names that have already been validated); instead, use
   * \Drupal\simpletest\TestBase::randomMachineName(). If $length is greater
   * than 2 the random string will include at least one ampersand ('&')
   * character to ensure coverage for special characters and avoid the
   * introduction of random test failures.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Pseudo-randomly generated unique string including special characters.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function randomString($length = 8) {
    if ($length < 3) {
      return $this->getRandomGenerator()->string($length, TRUE, array($this, 'randomStringValidate'));
    }

    // To prevent the introduction of random test failures, ensure that the
    // returned string contains a character that needs to be escaped in HTML by
    // injecting an ampersand into it.
    $replacement_pos = floor($length / 2);
    // Remove 1 from the length to account for the ampersand character.
    $string = $this->getRandomGenerator()->string($length - 1, TRUE, array($this, 'randomStringValidate'));
    return substr_replace($string, '&', $replacement_pos, 0);
  }

  /**
   * Callback for random string validation.
   *
   * @see \Drupal\Component\Utility\Random::string()
   *
   * @param string $string
   *   The random string to validate.
   *
   * @return bool
   *   TRUE if the random string is valid, FALSE if not.
   */
  public function randomStringValidate($string) {
    // Consecutive spaces causes issues for
    // Drupal\simpletest\WebTestBase::assertLink().
    if (preg_match('/\s{2,}/', $string)) {
      return FALSE;
    }

    // Starting with a space means that length might not be what is expected.
    // Starting with an @ sign causes CURL to fail if used in conjunction with a
    // file upload, see https://drupal.org/node/2174997.
    if (preg_match('/^(\s|@)/', $string)) {
      return FALSE;
    }

    // Ending with a space means that length might not be what is expected.
    if (preg_match('/\s$/', $string)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Generates a unique random string containing letters and numbers.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
   * \Drupal\simpletest\TestBase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated unique string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function randomMachineName($length = 8) {
    return $this->getRandomGenerator()->name($length, TRUE);
  }

  /**
   * Generates a random PHP object.
   *
   * @param int $size
   *   The number of random keys to add to the object.
   *
   * @return \stdClass
   *   The generated object, with the specified number of random keys. Each key
   *   has a random string value.
   *
   * @see \Drupal\Component\Utility\Random::object()
   */
  public function randomObject($size = 4) {
    return $this->getRandomGenerator()->object($size);
  }

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator
   */
  protected function getRandomGenerator() {
    if (!is_object($this->randomGenerator)) {
      $this->randomGenerator = new Random();
    }
    return $this->randomGenerator;
  }

  /**
   * Converts a list of possible parameters into a stack of permutations.
   *
   * Takes a list of parameters containing possible values, and converts all of
   * them into a list of items containing every possible permutation.
   *
   * Example:
   * @code
   * $parameters = array(
   *   'one' => array(0, 1),
   *   'two' => array(2, 3),
   * );
   * $permutations = TestBase::generatePermutations($parameters);
   * // Result:
   * $permutations == array(
   *   array('one' => 0, 'two' => 2),
   *   array('one' => 1, 'two' => 2),
   *   array('one' => 0, 'two' => 3),
   *   array('one' => 1, 'two' => 3),
   * )
   * @endcode
   *
   * @param $parameters
   *   An associative array of parameters, keyed by parameter name, and whose
   *   values are arrays of parameter values.
   *
   * @return
   *   A list of permutations, which is an array of arrays. Each inner array
   *   contains the full list of parameters that have been passed, but with a
   *   single value only.
   */
  public static function generatePermutations($parameters) {
    $all_permutations = array(array());
    foreach ($parameters as $parameter => $values) {
      $new_permutations = array();
      // Iterate over all values of the parameter.
      foreach ($values as $value) {
        // Iterate over all existing permutations.
        foreach ($all_permutations as $permutation) {
          // Add the new parameter value to existing permutations.
          $new_permutations[] = $permutation + array($parameter => $value);
        }
      }
      // Replace the old permutations with the new permutations.
      $all_permutations = $new_permutations;
    }
    return $all_permutations;
  }

  /**
   * Ensures test files are deletable within file_unmanaged_delete_recursive().
   *
   * Some tests chmod generated files to be read only. During
   * TestBase::restoreEnvironment() and other cleanup operations, these files
   * need to get deleted too.
   */
  public static function filePreDeleteCallback($path) {
    chmod($path, 0700);
  }

  /**
   * Returns a ConfigImporter object to import test importing of configuration.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter object.
   */
  public function configImporter() {
    if (!$this->configImporter) {
      // Set up the ConfigImporter object for testing.
      $storage_comparer = new StorageComparer(
        $this->container->get('config.storage.staging'),
        $this->container->get('config.storage'),
        $this->container->get('config.manager')
      );
      $this->configImporter = new ConfigImporter(
        $storage_comparer,
        $this->container->get('event_dispatcher'),
        $this->container->get('config.manager'),
        $this->container->get('lock'),
        $this->container->get('config.typed'),
        $this->container->get('module_handler'),
        $this->container->get('theme_handler'),
        $this->container->get('string_translation')
      );
    }
    // Always recalculate the changelist when called.
    return $this->configImporter->reset();
  }

  /**
   * Copies configuration objects from source storage to target storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source config storage service.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target config storage service.
   */
  public function copyConfig(StorageInterface $source_storage, StorageInterface $target_storage) {
    $target_storage->deleteAll();
    foreach ($source_storage->listAll() as $name) {
      $target_storage->write($name, $source_storage->read($name));
    }
  }
}
