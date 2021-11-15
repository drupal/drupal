<?php

/**
 * @file
 * Functions for error handling.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps PHP error constants to watchdog severity levels.
 *
 * The error constants are documented at
 * http://php.net/manual/errorfunc.constants.php
 *
 * @ingroup logging_severity_levels
 */
function drupal_error_levels() {
  $types = [
    E_ERROR => ['Error', RfcLogLevel::ERROR],
    E_WARNING => ['Warning', RfcLogLevel::WARNING],
    E_PARSE => ['Parse error', RfcLogLevel::ERROR],
    E_NOTICE => ['Notice', RfcLogLevel::NOTICE],
    E_CORE_ERROR => ['Core error', RfcLogLevel::ERROR],
    E_CORE_WARNING => ['Core warning', RfcLogLevel::WARNING],
    E_COMPILE_ERROR => ['Compile error', RfcLogLevel::ERROR],
    E_COMPILE_WARNING => ['Compile warning', RfcLogLevel::WARNING],
    E_USER_ERROR => ['User error', RfcLogLevel::ERROR],
    E_USER_WARNING => ['User warning', RfcLogLevel::WARNING],
    E_USER_NOTICE => ['User notice', RfcLogLevel::NOTICE],
    E_STRICT => ['Strict warning', RfcLogLevel::DEBUG],
    E_RECOVERABLE_ERROR => ['Recoverable fatal error', RfcLogLevel::ERROR],
    E_DEPRECATED => ['Deprecated function', RfcLogLevel::DEBUG],
    E_USER_DEPRECATED => ['User deprecated function', RfcLogLevel::DEBUG],
  ];

  return $types;
}

/**
 * Provides custom PHP error handling.
 *
 * @param $error_level
 *   The level of the error raised.
 * @param $message
 *   The error message.
 * @param $filename
 *   The filename that the error was raised in.
 * @param $line
 *   The line number the error was raised at.
 */
function _drupal_error_handler_real($error_level, $message, $filename, $line) {
  if ($error_level & error_reporting()) {
    $types = drupal_error_levels();
    [$severity_msg, $severity_level] = $types[$error_level];
    $backtrace = debug_backtrace();
    $caller = Error::getLastCaller($backtrace);

    // We treat recoverable errors as fatal.
    $recoverable = $error_level == E_RECOVERABLE_ERROR;
    // As __toString() methods must not throw exceptions (recoverable errors)
    // in PHP, we allow them to trigger a fatal error by emitting a user error
    // using trigger_error().
    $to_string = $error_level == E_USER_ERROR && substr($caller['function'], -strlen('__toString()')) == '__toString()';
    _drupal_log_error([
      '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
      // The standard PHP error handler considers that the error messages
      // are HTML. We mimic this behavior here.
      '@message' => Markup::create(Xss::filterAdmin($message)),
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => $severity_level,
      'backtrace' => $backtrace,
      '@backtrace_string' => (new \Exception())->getTraceAsString(),
      'exception' => NULL,
    ], $recoverable || $to_string);
  }
  // If the site is a test site then fail for user deprecations so they can be
  // caught by the deprecation error handler.
  elseif (DRUPAL_TEST_IN_CHILD_SITE && $error_level === E_USER_DEPRECATED) {
    static $seen = [];
    if (array_search($message, $seen, TRUE) === FALSE) {
      // Only report each deprecation once. Too many headers can break some
      // Chrome and web driver testing.
      $seen[] = $message;
      $backtrace = debug_backtrace();
      $caller = Error::getLastCaller($backtrace);
      _drupal_error_header(
        Markup::create(Xss::filterAdmin($message)),
        'User deprecated function',
        $caller['function'],
        $caller['file'],
        $caller['line']
      );
    }
  }
}

/**
 * Determines whether an error should be displayed.
 *
 * When in maintenance mode or when error_level is ERROR_REPORTING_DISPLAY_ALL,
 * all errors should be displayed. For ERROR_REPORTING_DISPLAY_SOME, $error
 * will be examined to determine if it should be displayed.
 *
 * @param $error
 *   Optional error to examine for ERROR_REPORTING_DISPLAY_SOME.
 *
 * @return
 *   TRUE if an error should be displayed.
 */
function error_displayable($error = NULL) {
  if (defined('MAINTENANCE_MODE')) {
    return TRUE;
  }
  $error_level = _drupal_get_error_level();
  if ($error_level == ERROR_REPORTING_DISPLAY_ALL || $error_level == ERROR_REPORTING_DISPLAY_VERBOSE) {
    return TRUE;
  }
  if ($error_level == ERROR_REPORTING_DISPLAY_SOME && isset($error)) {
    return $error['%type'] != 'Notice' && $error['%type'] != 'Strict warning';
  }
  return FALSE;
}

/**
 * Logs a PHP error or exception and displays an error page in fatal cases.
 *
 * @param $error
 *   An array with the following keys: %type, @message, %function, %file, %line,
 *   @backtrace_string, severity_level, backtrace, and exception. All the
 *   parameters are plain-text, with the exception of @message, which needs to
 *   be an HTML string, backtrace, which is a standard PHP backtrace, and
 *   exception, which is the exception object (or NULL if the error is not an
 *   exception).
 * @param bool $fatal
 *   TRUE for:
 *   - An exception is thrown and not caught by something else.
 *   - A recoverable fatal error, which is a fatal error.
 *   Non-recoverable fatal errors cannot be logged by Drupal.
 */
function _drupal_log_error($error, $fatal = FALSE) {
  $is_installer = InstallerKernel::installationAttempted();

  // Backtrace, exception and 'severity_level' are not valid replacement values
  // for t().
  $backtrace = $error['backtrace'];
  $exception = $error['exception'];
  $severity = $error['severity_level'];
  unset($error['backtrace'], $error['exception'], $error['severity_level']);

  // When running inside the testing framework, we relay the errors
  // to the tested site by the way of HTTP headers.
  if (DRUPAL_TEST_IN_CHILD_SITE && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
    _drupal_error_header($error['@message'], $error['%type'], $error['%function'], $error['%file'], $error['%line']);
  }

  $response = new Response();

  // Only call the logger if there is a logger factory available. This can occur
  // if there is an error while rebuilding the container or during the
  // installer.
  if (\Drupal::hasService('logger.factory')) {
    try {
      // Provide the PHP backtrace and exception to logger implementations. Add
      // 'severity_level' to the context to maintain BC and allow logging
      // implementations to use it.
      \Drupal::logger('php')->log($severity, '%type: @message in %function (line %line of %file) @backtrace_string.', $error + ['backtrace' => $backtrace, 'exception' => $exception, 'severity_level' => $severity]);
    }
    catch (\Exception $e) {
      // We can't log, for example because the database connection is not
      // available. At least try to log to PHP error log.
      error_log(strtr('Failed to log error: %type: @message in %function (line %line of %file). @backtrace_string', $error));
    }
  }

  // Log fatal errors, so developers can find and debug them.
  if ($fatal) {
    error_log(sprintf('%s: %s in %s on line %d %s', $error['%type'], $error['@message'], $error['%file'], $error['%line'], $error['@backtrace_string']));
  }

  if (PHP_SAPI === 'cli') {
    if ($fatal) {
      // When called from CLI, simply output a plain text message.
      // Should not translate the string to avoid errors producing more errors.
      $response->setContent(html_entity_decode(strip_tags(new FormattableMarkup('%type: @message in %function (line %line of %file).', $error))) . "\n");
      $response->send();
      exit(1);
    }
  }

  if (\Drupal::hasRequest() && \Drupal::request()->isXmlHttpRequest()) {
    if ($fatal) {
      if (error_displayable($error)) {
        // When called from JavaScript, simply output the error message.
        // Should not translate the string to avoid errors producing more errors.
        $response->setContent(new FormattableMarkup('%type: @message in %function (line %line of %file).', $error));
        $response->send();
      }
      exit;
    }
  }
  else {
    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionally in update.php.
    $message = '';
    $class = NULL;
    if (error_displayable($error)) {
      $class = 'error';

      // If error type is 'User notice' then treat it as debug information
      // instead of an error message.
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
        $class = 'status';
      }

      // Attempt to reduce verbosity by removing DRUPAL_ROOT from the file path
      // in the message. This does not happen for (false) security.
      if (\Drupal::hasService('kernel')) {
        $root_length = strlen(\Drupal::root());
        if (substr($error['%file'], 0, $root_length) == \Drupal::root()) {
          $error['%file'] = substr($error['%file'], $root_length + 1);
        }
      }

      // Check if verbose error reporting is on.
      $error_level = _drupal_get_error_level();

      if ($error_level != ERROR_REPORTING_DISPLAY_VERBOSE) {
        // Without verbose logging, use a simple message.

        // We use \Drupal\Component\Render\FormattableMarkup directly here,
        // rather than use t() since we are in the middle of error handling, and
        // we don't want t() to cause further errors.
        $message = new FormattableMarkup('%type: @message in %function (line %line of %file).', $error);
      }
      else {
        // With verbose logging, we will also include a backtrace.

        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);
        // Generate a backtrace containing only scalar argument values.
        $error['@backtrace'] = Error::formatBacktrace($backtrace);
        $message = new FormattableMarkup('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    if ($fatal) {
      // We fallback to a maintenance page at this point, because the page generation
      // itself can generate errors.
      // Should not translate the string to avoid errors producing more errors.
      $message = 'The website encountered an unexpected error. Please try again later.' . '<br />' . $message;

      if ($is_installer) {
        // install_display_output() prints the output and ends script execution.
        $output = [
          '#title' => 'Error',
          '#markup' => $message,
        ];
        install_display_output($output, $GLOBALS['install_state']);
        exit;
      }

      $response->setContent($message);
      $response->setStatusCode(500, '500 Service unavailable (with message)');

      $response->send();
      // An exception must halt script execution.
      exit;
    }

    if ($message) {
      if (\Drupal::hasService('session')) {
        // Message display is dependent on sessions being available.
        \Drupal::messenger()->addMessage($message, $class, TRUE);
      }
      else {
        print $message;
      }
    }
  }
}

/**
 * Returns the current error level.
 *
 * This function should only be used to get the current error level prior to the
 * kernel being booted or before Drupal is installed. In all other situations
 * the following code is preferred:
 * @code
 * \Drupal::config('system.logging')->get('error_level');
 * @endcode
 *
 * @return string
 *   The current error level.
 */
function _drupal_get_error_level() {
  // Raise the error level to maximum for the installer, so users are able to
  // file proper bug reports for installer errors. The returned value is
  // different to the one below, because the installer actually has a
  // 'config.factory' service, which reads the default 'error_level' value from
  // System module's default configuration and the default value is not verbose.
  // @see error_displayable()
  if (InstallerKernel::installationAttempted()) {
    return ERROR_REPORTING_DISPLAY_VERBOSE;
  }
  $error_level = NULL;
  // Try to get the error level configuration from database. If this fails,
  // for example if the database connection is not there, try to read it from
  // settings.php.
  try {
    $error_level = \Drupal::config('system.logging')->get('error_level');
  }
  catch (\Exception $e) {
    $error_level = $GLOBALS['config']['system.logging']['error_level'] ?? ERROR_REPORTING_HIDE;
  }

  // If there is no container or if it has no config.factory service, we are
  // possibly in an edge-case error situation while trying to serve a regular
  // request on a public site, so use the non-verbose default value.
  return $error_level ?: ERROR_REPORTING_DISPLAY_ALL;
}

/**
 * Adds error information to headers so that tests can access it.
 *
 * @param $message
 *   The error message.
 * @param $type
 *   The type of error.
 * @param $function
 *   The function that emitted the error.
 * @param $file
 *   The file that emitted the error.
 * @param $line
 *   The line number in file that emitted the error.
 */
function _drupal_error_header($message, $type, $function, $file, $line) {
  // $number does not use drupal_static as it should not be reset
  // as it uniquely identifies each PHP error.
  static $number = 0;
  $assertion = [
    $message,
    $type,
    [
      'function' => $function,
      'file' => $file,
      'line' => $line,
    ],
  ];
  // For non-fatal errors (e.g. PHP notices) _drupal_log_error can be called
  // multiple times per request. In that case the response is typically
  // generated outside of the error handler, e.g., in a controller. As a
  // result it is not possible to use a Response object here but instead the
  // headers need to be emitted directly.
  header('X-Drupal-Assertion-' . $number . ': ' . rawurlencode(serialize($assertion)));
  $number++;
}
