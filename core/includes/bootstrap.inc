<?php

/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Http\InputBag;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Test\TestDatabase;
use Drupal\Core\Utility\Error;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\InputBag as SymfonyInputBag;

/**
 * Minimum allowed version of PHP for Drupal to be bootstrapped.
 *
 * Below this version:
 * - The installer cannot be run.
 * - Updates cannot be run.
 * - Modules and themes cannot be enabled.
 * - If a site managed to bypass all of the above, then an error is shown in
 *   the status report and various fatal errors occur on various pages.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal::MINIMUM_PHP instead.
 *
 * @see https://www.drupal.org/node/2909361
 * @see install.php
 */
const DRUPAL_MINIMUM_PHP = \Drupal::MINIMUM_PHP;

/**
 * Minimum supported version of PHP.
 *
 * Below this version:
 * - New sites cannot be installed, except from within tests.
 * - Updates from previous Drupal versions can be run, but users are warned
 *   that Drupal no longer supports that PHP version.
 * - An error is shown in the status report that the PHP version is too old.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal::MINIMUM_SUPPORTED_PHP instead.
 *
 * @see https://www.drupal.org/node/2909361
 */
const DRUPAL_MINIMUM_SUPPORTED_PHP = \Drupal::MINIMUM_SUPPORTED_PHP;

/**
 * Minimum recommended version of PHP.
 *
 * Sites installing Drupal on PHP versions lower than this will see a warning
 * message, but Drupal can still be installed. Used for (e.g.) PHP versions
 * that have reached their EOL or will in the near future.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal::RECOMMENDED_PHP instead.
 *
 * @see https://www.drupal.org/node/2909361
 */
const DRUPAL_RECOMMENDED_PHP = \Drupal::RECOMMENDED_PHP;

/**
 * Minimum recommended value of PHP memory_limit.
 *
 * 64M was chosen as a minimum requirement in order to allow for additional
 * contributed modules to be installed prior to hitting the limit. However,
 * 40M is the target for the Standard installation profile.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal::MINIMUM_PHP_MEMORY_LIMIT instead.
 *
 * @see https://www.drupal.org/node/2909361
 */
const DRUPAL_MINIMUM_PHP_MEMORY_LIMIT = \Drupal::MINIMUM_PHP_MEMORY_LIMIT;

/**
 * Error reporting level: display no errors.
 */
const ERROR_REPORTING_HIDE = 'hide';

/**
 * Error reporting level: display errors and warnings.
 */
const ERROR_REPORTING_DISPLAY_SOME = 'some';

/**
 * Error reporting level: display all messages.
 */
const ERROR_REPORTING_DISPLAY_ALL = 'all';

/**
 * Error reporting level: display all messages, plus backtrace information.
 */
const ERROR_REPORTING_DISPLAY_VERBOSE = 'verbose';

/**
 * The maximum number of characters in a module or theme name.
 */
const DRUPAL_EXTENSION_NAME_MAX_LENGTH = 50;

/**
 * Time of the current request in seconds elapsed since the Unix Epoch.
 *
 * This differs from $_SERVER['REQUEST_TIME'], which is stored as a float
 * since PHP 5.4.0. Float timestamps confuse most PHP functions
 * (including date_create()).
 *
 * @see http://php.net/manual/reserved.variables.server.php
 * @see http://php.net/manual/function.time.php
 *
 * @deprecated in drupal:8.3.0 and is removed from drupal:10.0.0.
 *   Use \Drupal::time()->getRequestTime();
 *
 * @see https://www.drupal.org/node/2785211
 */
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

/**
 * Defines the root directory of the Drupal installation.
 *
 * This strips two levels of directories off the current directory.
 */
define('DRUPAL_ROOT', dirname(__DIR__, 2));

/**
 * Declare class aliases that will be added by the autoloader.
 */
// @todo https://www.drupal.org/project/drupal/issues/3197482 Remove this class
//   alias once Drupal is running Symfony 5.3 or higher.
class_alias('Drupal\Core\Http\KernelEvent', 'Symfony\Component\HttpKernel\Event\KernelEvent', TRUE);

/**
 * Set up the Symfony Request factory for forward compatibility with Symfony 5.
 *
 * @todo Remove this when Symfony 4 is no longer supported.
 */
Request::setFactory(
  function ($query, $request, $attributes, $cookies, $files, $server, $content) {
    $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);
    foreach (['request', 'query', 'cookies'] as $bag) {
      if (!($bag instanceof SymfonyInputBag)) {
        $request->$bag = new InputBag($request->$bag->all());
      }
    }
    return $request;
  }
);

/**
 * Returns and optionally sets the filename for a system resource.
 *
 * The filename, whether provided, cached, or retrieved from the database, is
 * only returned if the file exists.
 *
 * This function plays a key role in allowing Drupal's resources (modules
 * and themes) to be located in different places depending on a site's
 * configuration. For example, a module 'foo' may legally be located
 * in any of these three places:
 *
 * core/modules/foo/foo.info.yml
 * modules/foo/foo.info.yml
 * sites/example.com/modules/foo/foo.info.yml
 *
 * Calling drupal_get_filename('module', 'foo') will give you one of
 * the above, depending on where the module is located.
 *
 * @param $type
 *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
 *   'theme_engine'.
 * @param $name
 *   The name of the item for which the filename is requested. Ignored for
 *   $type 'core'.
 * @param $filename
 *   The filename of the item if it is to be set explicitly rather
 *   than by consulting the database.
 *
 * @return string
 *   The filename of the requested item or NULL if the item is not found.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Core\Extension\ExtensionPathResolver::getPathname() instead.
 *
 * @see https://www.drupal.org/node/2940438
 */
function drupal_get_filename($type, $name, $filename = NULL) {
  @trigger_error('drupal_get_filename() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionPathResolver::getPathname() instead. See https://www.drupal.org/node/2940438', E_USER_DEPRECATED);
  // Type 'core' only exists to simplify application-level logic; it always maps
  // to the /core directory, whereas $name is ignored. It is only requested via
  // \Drupal\Core\Extension\ExtensionList::getPath(). The file
  // /core/core.info.yml does not exist, but is required since
  // ExtensionList::getPath() returns the dirname() of the returned pathname.
  if ($type === 'core') {
    return 'core/core.info.yml';
  }

  try {
    /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
    $extension_list = \Drupal::service("extension.list.$type");
    if (isset($filename)) {
      // Manually add the info file path of an extension.
      $extension_list->setPathname($name, $filename);
    }
    return $extension_list->getPathname($name);
  }
  catch (ServiceNotFoundException $e) {
    // Catch the exception. This will result in triggering an error.
    // If the service is unknown, create a user-level error message.
    trigger_error(
      sprintf('Unknown type specified: "%s". Must be one of: "core", "profile", "module", "theme", or "theme_engine".', $type),
      E_USER_WARNING
    );
  }
  catch (\InvalidArgumentException $e) {
    // Catch the exception. This will result in triggering an error.
    // If the filename is still unknown, create a user-level error message.
    trigger_error(
      sprintf('The following %s is missing from the file system: %s', $type, $name),
      E_USER_WARNING
    );
  }
}

/**
 * Returns the path to a system item (module, theme, etc.).
 *
 * @param $type
 *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
 *   'theme_engine'.
 * @param $name
 *   The name of the item for which the path is requested. Ignored for
 *   $type 'core'.
 *
 * @return string
 *   The path to the requested item or an empty string if the item is not found.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Core\Extension\ExtensionPathResolver::getPath() instead.
 *
 * @see https://www.drupal.org/node/2940438
 * @see \Drupal\Core\Extension\ExtensionList::getPath()
 */
function drupal_get_path($type, $name) {
  @trigger_error('drupal_get_path() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Extension\ExtensionPathResolver::getPath() instead. See https://www.drupal.org/node/2940438', E_USER_DEPRECATED);
  return dirname(drupal_get_filename($type, $name));
}

/**
 * Translates a string to the current language or to a given language.
 *
 * In order for strings to be localized, make them available in one of the ways
 * supported by the @link i18n Localization API. @endlink When possible, use
 * the \Drupal\Core\StringTranslation\StringTranslationTrait $this->t().
 * Otherwise create a new \Drupal\Core\StringTranslation\TranslatableMarkup
 * object directly.
 *
 * See \Drupal\Core\StringTranslation\TranslatableMarkup::__construct() for
 * important security information and usage guidelines.
 *
 * @param string $string
 *   A string containing the English text to translate.
 * @param array $args
 *   (optional) An associative array of replacements to make after translation.
 *   Based on the first character of the key, the value is escaped and/or
 *   themed. See
 *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
 *   details.
 * @param array $options
 *   (optional) An associative array of additional options, with the following
 *   elements:
 *   - 'langcode' (defaults to the current language): A language code, to
 *     translate to a language other than what is used to display the page.
 *   - 'context' (defaults to the empty context): The context the source string
 *     belongs to. See the @link i18n Internationalization topic @endlink for
 *     more information about string contexts.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup
 *   An object that, when cast to a string, returns the translated string.
 *
 * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
 * @see \Drupal\Core\StringTranslation\StringTranslationTrait::t()
 * @see \Drupal\Core\StringTranslation\TranslatableMarkup::__construct()
 *
 * @ingroup sanitization
 */
function t($string, array $args = [], array $options = []) {
  return new TranslatableMarkup($string, $args, $options);
}

/**
 * Logs an exception.
 *
 * This is a wrapper logging function which automatically decodes an exception.
 *
 * @param $type
 *   The category to which this message belongs.
 * @param $exception
 *   The exception that is going to be logged.
 * @param $message
 *   The message to store in the log. If empty, a text that contains all useful
 *   information about the passed-in exception is used.
 * @param $variables
 *   Array of variables to replace in the message on display or
 *   NULL if message is already translated or not possible to
 *   translate.
 * @param $severity
 *   The severity of the message, as per RFC 3164.
 * @param $link
 *   A link to associate with the message.
 *
 * @see \Drupal\Core\Utility\Error::decodeException()
 */
function watchdog_exception($type, Exception $exception, $message = NULL, $variables = [], $severity = RfcLogLevel::ERROR, $link = NULL) {

  // Use a default value if $message is not set.
  if (empty($message)) {
    $message = '%type: @message in %function (line %line of %file).';
  }

  if ($link) {
    $variables['link'] = $link;
  }

  $variables += Error::decodeException($exception);

  \Drupal::logger($type)->log($severity, $message, $variables);
}

/**
 * Provides custom PHP error handling.
 *
 * @param $error_level
 *   The level of the error raised.
 * @param $message
 *   The error message.
 * @param $filename
 *   (optional) The filename that the error was raised in.
 * @param $line
 *   (optional) The line number the error was raised at.
 */
function _drupal_error_handler($error_level, $message, $filename = NULL, $line = NULL) {
  require_once __DIR__ . '/errors.inc';
  _drupal_error_handler_real($error_level, $message, $filename, $line);
}

/**
 * Provides custom PHP exception handling.
 *
 * Uncaught exceptions are those not enclosed in a try/catch block. They are
 * always fatal: the execution of the script will stop as soon as the exception
 * handler exits.
 *
 * @param \Exception|\Throwable $exception
 *   The exception object that was thrown.
 */
function _drupal_exception_handler($exception) {
  require_once __DIR__ . '/errors.inc';

  try {
    // Log the message to the watchdog and return an error page to the user.
    _drupal_log_error(Error::decodeException($exception), TRUE);
  }
  // Catch \Throwable, which covers both Error and Exception throwables.
  catch (\Throwable $error) {
    _drupal_exception_handler_additional($exception, $error);
  }
}

/**
 * Displays any additional errors caught while handling an exception.
 *
 * @param \Exception|\Throwable $exception
 *   The first exception object that was thrown.
 * @param \Exception|\Throwable $exception2
 *   The second exception object that was thrown.
 */
function _drupal_exception_handler_additional($exception, $exception2) {
  // Another uncaught exception was thrown while handling the first one.
  // If we are displaying errors, then do so with no possibility of a further
  // uncaught exception being thrown.
  if (error_displayable()) {
    print '<h1>Additional uncaught exception thrown while handling exception.</h1>';
    print '<h2>Original</h2><p>' . Error::renderExceptionSafe($exception) . '</p>';
    print '<h2>Additional</h2><p>' . Error::renderExceptionSafe($exception2) . '</p><hr />';
  }
}

/**
 * Returns the test prefix if this is an internal request from SimpleTest.
 *
 * @param string $new_prefix
 *   Internal use only. A new prefix to be stored.
 *
 * @return string|false
 *   Either the simpletest prefix (the string "simpletest" followed by any
 *   number of digits) or FALSE if the user agent does not contain a valid
 *   HMAC and timestamp.
 */
function drupal_valid_test_ua($new_prefix = NULL) {
  static $test_prefix;

  if (isset($new_prefix)) {
    $test_prefix = $new_prefix;
  }
  if (isset($test_prefix)) {
    return $test_prefix;
  }
  // Unless the below User-Agent and HMAC validation succeeds, we are not in
  // a test environment.
  $test_prefix = FALSE;

  // A valid Simpletest request will contain a hashed and salted authentication
  // code. Check if this code is present in a cookie or custom user agent
  // string.
  $http_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? NULL;
  $user_agent = $_COOKIE['SIMPLETEST_USER_AGENT'] ?? $http_user_agent;
  if (isset($user_agent) && preg_match("/^simple(\w+\d+):(.+):(.+):(.+)$/", $user_agent, $matches)) {
    [, $prefix, $time, $salt, $hmac] = $matches;
    $check_string = $prefix . ':' . $time . ':' . $salt;
    // Read the hash salt prepared by drupal_generate_test_ua().
    // This function is called before settings.php is read and Drupal's error
    // handlers are set up. While Drupal's error handling may be properly
    // configured on production sites, the server's PHP error_reporting may not.
    // Ensure that no information leaks on production sites.
    $test_db = new TestDatabase($prefix);
    $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
    if (!is_readable($key_file) || is_dir($key_file)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      exit;
    }
    $private_key = file_get_contents($key_file);
    // The string from drupal_generate_test_ua() is 74 bytes long. If we don't
    // have it, tests cannot be allowed.
    if (empty($private_key) || strlen($private_key) < 74) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      exit;
    }
    // The file properties add more entropy not easily accessible to others.
    $key = $private_key . filectime(__FILE__) . fileinode(__FILE__);
    $time_diff = REQUEST_TIME - $time;
    $test_hmac = Crypt::hmacBase64($check_string, $key);
    // Since we are making a local request a 600 second time window is allowed,
    // and the HMAC must match.
    if ($time_diff >= 0 && $time_diff <= 600 && hash_equals($test_hmac, $hmac)) {
      $test_prefix = $prefix;
    }
    else {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden (SIMPLETEST_USER_AGENT invalid)');
      exit;
    }
  }
  return $test_prefix;
}

/**
 * Generates a user agent string with a HMAC and timestamp for simpletest.
 */
function drupal_generate_test_ua($prefix) {
  static $key, $last_prefix;

  if (!isset($key) || $last_prefix != $prefix) {
    $last_prefix = $prefix;
    $test_db = new TestDatabase($prefix);
    $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
    // When issuing an outbound HTTP client request from within an inbound test
    // request, then the outbound request has to use the same User-Agent header
    // as the inbound request. A newly generated private key for the same test
    // prefix would invalidate all subsequent inbound requests.
    // @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware
    if (DRUPAL_TEST_IN_CHILD_SITE && $parent_prefix = drupal_valid_test_ua()) {
      if ($parent_prefix != $prefix) {
        throw new \RuntimeException("Malformed User-Agent: Expected '$parent_prefix' but got '$prefix'.");
      }
      // If the file is not readable, a PHP warning is expected in this case.
      $private_key = file_get_contents($key_file);
    }
    else {
      // Generate and save a new hash salt for a test run.
      // Consumed by drupal_valid_test_ua() before settings.php is loaded.
      $private_key = Crypt::randomBytesBase64(55);
      file_put_contents($key_file, $private_key);
    }
    // The file properties add more entropy not easily accessible to others.
    $key = $private_key . filectime(__FILE__) . fileinode(__FILE__);
  }
  // Generate a moderately secure HMAC based on the database credentials.
  $salt = uniqid('', TRUE);
  $check_string = $prefix . ':' . time() . ':' . $salt;
  return 'simple' . $check_string . ':' . Crypt::hmacBase64($check_string, $key);
}

/**
 * Enables use of the theme system without requiring database access.
 *
 * Loads and initializes the theme system for site installs, updates and when
 * the site is in maintenance mode. This also applies when the database fails.
 *
 * @see _drupal_maintenance_theme()
 */
function drupal_maintenance_theme() {
  require_once __DIR__ . '/theme.maintenance.inc';
  _drupal_maintenance_theme();
}

/**
 * Provides central static variable storage.
 *
 * All functions requiring a static variable to persist or cache data within
 * a single page request are encouraged to use this function unless it is
 * absolutely certain that the static variable will not need to be reset during
 * the page request. By centralizing static variable storage through this
 * function, other functions can rely on a consistent API for resetting any
 * other function's static variables.
 *
 * Example:
 * @code
 * function example_list($field = 'default') {
 *   $examples = &drupal_static(__FUNCTION__);
 *   if (!isset($examples)) {
 *     // If this function is being called for the first time after a reset,
 *     // query the database and execute any other code needed to retrieve
 *     // information.
 *     ...
 *   }
 *   if (!isset($examples[$field])) {
 *     // If this function is being called for the first time for a particular
 *     // index field, then execute code needed to index the information already
 *     // available in $examples by the desired field.
 *     ...
 *   }
 *   // Subsequent invocations of this function for a particular index field
 *   // skip the above two code blocks and quickly return the already indexed
 *   // information.
 *   return $examples[$field];
 * }
 * function examples_admin_overview() {
 *   // When building the content for the overview page, make sure to get
 *   // completely fresh information.
 *   drupal_static_reset('example_list');
 *   ...
 * }
 * @endcode
 *
 * In a few cases, a function can have certainty that there is no legitimate
 * use-case for resetting that function's static variable. This is rare,
 * because when writing a function, it's hard to forecast all the situations in
 * which it will be used. A guideline is that if a function's static variable
 * does not depend on any information outside of the function that might change
 * during a single page request, then it's ok to use the "static" keyword
 * instead of the drupal_static() function.
 *
 * Example:
 * @code
 * function mymodule_log_stream_handle($new_handle = NULL) {
 *   static $handle;
 *   if (isset($new_handle)) {
 *     $handle = $new_handle;
 *   }
 *   return $handle;
 * }
 * @endcode
 *
 * In a few cases, a function needs a resettable static variable, but the
 * function is called many times (100+) during a single page request, so
 * every microsecond of execution time that can be removed from the function
 * counts. These functions can use a more cumbersome, but faster variant of
 * calling drupal_static(). It works by storing the reference returned by
 * drupal_static() in the calling function's own static variable, thereby
 * removing the need to call drupal_static() for each iteration of the function.
 * Conceptually, it replaces:
 * @code
 * $foo = &drupal_static(__FUNCTION__);
 * @endcode
 * with:
 * @code
 * // Unfortunately, this does not work.
 * static $foo = &drupal_static(__FUNCTION__);
 * @endcode
 * However, the above line of code does not work, because PHP only allows static
 * variables to be initialized by literal values, and does not allow static
 * variables to be assigned to references.
 * - http://php.net/manual/language.variables.scope.php#language.variables.scope.static
 * - http://php.net/manual/language.variables.scope.php#language.variables.scope.references
 * The example below shows the syntax needed to work around both limitations.
 * For benchmarks and more information, see https://www.drupal.org/node/619666.
 *
 * Example:
 * @code
 * function example_default_format_type() {
 *   // Use the advanced drupal_static() pattern, since this is called very often.
 *   static $drupal_static_fast;
 *   if (!isset($drupal_static_fast)) {
 *     $drupal_static_fast['format_type'] = &drupal_static(__FUNCTION__);
 *   }
 *   $format_type = &$drupal_static_fast['format_type'];
 *   ...
 * }
 * @endcode
 *
 * @param $name
 *   Globally unique name for the variable. For a function with only one static,
 *   variable, the function name (e.g. via the PHP magic __FUNCTION__ constant)
 *   is recommended. For a function with multiple static variables add a
 *   distinguishing suffix to the function name for each one.
 * @param $default_value
 *   Optional default value.
 * @param $reset
 *   TRUE to reset one or all variables(s). This parameter is only used
 *   internally and should not be passed in; use drupal_static_reset() instead.
 *   (This function's return value should not be used when TRUE is passed in.)
 *
 * @return mixed
 *   Returns a variable by reference.
 *
 * @see drupal_static_reset()
 */
function &drupal_static($name, $default_value = NULL, $reset = FALSE) {
  static $data = [], $default = [];
  // First check if dealing with a previously defined static variable.
  if (isset($data[$name]) || array_key_exists($name, $data)) {
    // Non-NULL $name and both $data[$name] and $default[$name] statics exist.
    if ($reset) {
      // Reset pre-existing static variable to its default value.
      $data[$name] = $default[$name];
    }
    return $data[$name];
  }
  // Neither $data[$name] nor $default[$name] static variables exist.
  if (isset($name)) {
    if ($reset) {
      // Reset was called before a default is set and yet a variable must be
      // returned.
      return $data;
    }
    // First call with new non-NULL $name. Initialize a new static variable.
    $default[$name] = $data[$name] = $default_value;
    return $data[$name];
  }
  // Reset all: ($name == NULL). This needs to be done one at a time so that
  // references returned by earlier invocations of drupal_static() also get
  // reset.
  foreach ($default as $name => $value) {
    $data[$name] = $value;
  }
  // As the function returns a reference, the return should always be a
  // variable.
  return $data;
}

/**
 * Resets one or all centrally stored static variable(s).
 *
 * @param $name
 *   Name of the static variable to reset. Omit to reset all variables.
 *   Resetting all variables should only be used, for example, for running
 *   unit tests with a clean environment.
 */
function drupal_static_reset($name = NULL) {
  switch ($name) {
    case 'taxonomy_vocabulary_get_names':
      @trigger_error("Calling drupal_static_reset() with 'taxonomy_vocabulary_get_names' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3039041", E_USER_DEPRECATED);
      break;

    case 'node_mark':
      @trigger_error("Calling drupal_static_reset() with 'node_mark' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3037203", E_USER_DEPRECATED);
      break;

    case 'Drupal\book\BookManager::bookSubtreeData':
    case 'Drupal\book\BookManager::bookTreeAllData':
    case 'Drupal\book\BookManager::doBookTreeBuild':
      @trigger_error("Calling drupal_static_reset() with '{$name}' as argument is deprecated in drupal:9.3.0 and is removed in drupal:10.0.0. Use \Drupal::service('book.memory_cache')->deleteAll() instead. See https://www.drupal.org/node/3039439", E_USER_DEPRECATED);
      \Drupal::service('book.memory_cache')->deleteAll();
      break;

  }
  drupal_static($name, NULL, TRUE);
}

/**
 * Registers a function for execution on shutdown.
 *
 * Wrapper for register_shutdown_function() that catches thrown exceptions to
 * avoid "Exception thrown without a stack frame in Unknown".
 *
 * @param callable $callback
 *   The shutdown function to register.
 * @param ...
 *   Additional arguments to pass to the shutdown function.
 *
 * @return array
 *   Array of shutdown functions to be executed.
 *
 * @see register_shutdown_function()
 * @ingroup php_wrappers
 */
function &drupal_register_shutdown_function($callback = NULL) {
  // We cannot use drupal_static() here because the static cache is reset during
  // batch processing, which breaks batch handling.
  static $callbacks = [];

  if (isset($callback)) {
    // Only register the internal shutdown function once.
    if (empty($callbacks)) {
      register_shutdown_function('_drupal_shutdown_function');
    }
    $args = func_get_args();
    // Remove $callback from the arguments.
    unset($args[0]);
    // Save callback and arguments
    $callbacks[] = ['callback' => $callback, 'arguments' => $args];
  }
  return $callbacks;
}

/**
 * Executes registered shutdown functions.
 */
function _drupal_shutdown_function() {
  $callbacks = &drupal_register_shutdown_function();

  // Set the CWD to DRUPAL_ROOT as it is not guaranteed to be the same as it
  // was in the normal context of execution.
  chdir(DRUPAL_ROOT);

  try {
    reset($callbacks);
    // Do not use foreach() here because it is possible that the callback will
    // add to the $callbacks array via drupal_register_shutdown_function().
    while ($callback = current($callbacks)) {
      call_user_func_array($callback['callback'], $callback['arguments']);
      next($callbacks);
    }
  }
  // Catch \Throwable, which covers both Error and Exception throwables.
  catch (\Throwable $error) {
    _drupal_shutdown_function_handle_exception($error);
  }
}

/**
 * Displays and logs any errors that may happen during shutdown.
 *
 * @param \Exception|\Throwable $exception
 *   The exception object that was thrown.
 *
 * @see _drupal_shutdown_function()
 */
function _drupal_shutdown_function_handle_exception($exception) {
  // If using PHP-FPM then fastcgi_finish_request() will have been fired
  // preventing further output to the browser.
  if (!function_exists('fastcgi_finish_request')) {
    // If we are displaying errors, then do so with no possibility of a
    // further uncaught exception being thrown.
    require_once __DIR__ . '/errors.inc';
    if (error_displayable()) {
      print '<h1>Uncaught exception thrown in shutdown function.</h1>';
      print '<p>' . Error::renderExceptionSafe($exception) . '</p><hr />';
    }
  }
  error_log($exception);
}
