<?php

/**
 * @file
 * Functions for error handling.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
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
  $types = array(
    E_ERROR => array('Error', RfcLogLevel::ERROR),
    E_WARNING => array('Warning', RfcLogLevel::WARNING),
    E_PARSE => array('Parse error', RfcLogLevel::ERROR),
    E_NOTICE => array('Notice', RfcLogLevel::NOTICE),
    E_CORE_ERROR => array('Core error', RfcLogLevel::ERROR),
    E_CORE_WARNING => array('Core warning', RfcLogLevel::WARNING),
    E_COMPILE_ERROR => array('Compile error', RfcLogLevel::ERROR),
    E_COMPILE_WARNING => array('Compile warning', RfcLogLevel::WARNING),
    E_USER_ERROR => array('User error', RfcLogLevel::ERROR),
    E_USER_WARNING => array('User warning', RfcLogLevel::WARNING),
    E_USER_NOTICE => array('User notice', RfcLogLevel::NOTICE),
    E_STRICT => array('Strict warning', RfcLogLevel::DEBUG),
    E_RECOVERABLE_ERROR => array('Recoverable fatal error', RfcLogLevel::ERROR),
    E_DEPRECATED => array('Deprecated function', RfcLogLevel::DEBUG),
    E_USER_DEPRECATED => array('User deprecated function', RfcLogLevel::DEBUG),
  );

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
 * @param $context
 *   An array that points to the active symbol table at the point the error
 *   occurred.
 */
function _drupal_error_handler_real($error_level, $message, $filename, $line, $context) {
  if ($error_level & error_reporting()) {
    $types = drupal_error_levels();
    list($severity_msg, $severity_level) = $types[$error_level];
    $backtrace = debug_backtrace();
    $caller = Error::getLastCaller($backtrace);

    // We treat recoverable errors as fatal.
    $recoverable = $error_level == E_RECOVERABLE_ERROR;
    // As __toString() methods must not throw exceptions (recoverable errors)
    // in PHP, we allow them to trigger a fatal error by emitting a user error
    // using trigger_error().
    $to_string = $error_level == E_USER_ERROR && substr($caller['function'], -strlen('__toString()')) == '__toString()';
    _drupal_log_error(array(
      '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
      // The standard PHP error handler considers that the error messages
      // are HTML. We mimick this behavior here.
      '@message' => Markup::create(Xss::filterAdmin($message)),
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => $severity_level,
      'backtrace' => $backtrace,
      '@backtrace_string' => (new \Exception())->getTraceAsString(),
    ), $recoverable || $to_string);
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
 *   An array with the following keys: %type, @message, %function, %file,
 *   %line, @backtrace_string, severity_level, and backtrace. All the parameters
 *   are plain-text, with the exception of @message, which needs to be an HTML
 *   string, and backtrace, which is a standard PHP backtrace.
 * @param bool $fatal
 *   TRUE for:
 *   - An exception is thrown and not caught by something else.
 *   - A recoverable fatal error, which is a fatal error.
 *   Non-recoverable fatal errors cannot be logged by Drupal.
 */
function _drupal_log_error($error, $fatal = FALSE) {
  $is_installer = drupal_installation_attempted();

  // Backtrace array is not a valid replacement value for t().
  $backtrace = $error['backtrace'];
  unset($error['backtrace']);

  // When running inside the testing framework, we relay the errors
  // to the tested site by the way of HTTP headers.
  if (DRUPAL_TEST_IN_CHILD_SITE && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
    // $number does not use drupal_static as it should not be reset
    // as it uniquely identifies each PHP error.
    static $number = 0;
    $assertion = array(
      $error['@message'],
      $error['%type'],
      array(
        'function' => $error['%function'],
        'file' => $error['%file'],
        'line' => $error['%line'],
      ),
    );
    // For non-fatal errors (e.g. PHP notices) _drupal_log_error can be called
    // multiple times per request. In that case the response is typically
    // generated outside of the error handler, e.g., in a controller. As a
    // result it is not possible to use a Response object here but instead the
    // headers need to be emitted directly.
    header('X-Drupal-Assertion-' . $number . ': ' . rawurlencode(serialize($assertion)));
    $number++;
  }

  $response = new Response();

  // Only call the logger if there is a logger factory available. This can occur
  // if there is an error while rebuilding the container or during the
  // installer.
  if (\Drupal::hasService('logger.factory')) {
    try {
      \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file) @backtrace_string.', $error);
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
      $response->setContent(html_entity_decode(strip_tags(SafeMarkup::format('%type: @message in %function (line %line of %file).', $error))) . "\n");
      $response->send();
      exit;
    }
  }

  if (\Drupal::hasRequest() && \Drupal::request()->isXmlHttpRequest()) {
    if ($fatal) {
      if (error_displayable($error)) {
        // When called from JavaScript, simply output the error message.
        // Should not translate the string to avoid errors producing more errors.
        $response->setContent(SafeMarkup::format('%type: @message in %function (line %line of %file).', $error));
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
      // @see debug()
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
        $class = 'status';
      }

      // Attempt to reduce verbosity by removing DRUPAL_ROOT from the file path
      // in the message. This does not happen for (false) security.
      if (\Drupal::hasService('app.root')) {
        $root_length = strlen(\Drupal::root());
        if (substr($error['%file'], 0, $root_length) == \Drupal::root()) {
          $error['%file'] = substr($error['%file'], $root_length + 1);
        }
      }

      // Check if verbose error reporting is on.
      $error_level = _drupal_get_error_level();

      if ($error_level != ERROR_REPORTING_DISPLAY_VERBOSE) {
        // Without verbose logging, use a simple message.

        // We call SafeMarkup::format() directly here, rather than use t() since
        // we are in the middle of error handling, and we don't want t() to
        // cause further errors.
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file).', $error);
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
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    if ($fatal) {
      // We fallback to a maintenance page at this point, because the page generation
      // itself can generate errors.
      // Should not translate the string to avoid errors producing more errors.
      $message = 'The website encountered an unexpected error. Please try again later.' . '<br />' . $message;

      if ($is_installer) {
        // install_display_output() prints the output and ends script execution.
        $output = array(
          '#title' => 'Error',
          '#markup' => $message,
        );
        install_display_output($output, $GLOBALS['install_state'], $response->headers->all());
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
        drupal_set_message($message, $class, TRUE);
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
  if (drupal_installation_attempted()) {
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
    $error_level = isset($GLOBALS['config']['system.logging']['error_level']) ? $GLOBALS['config']['system.logging']['error_level'] : ERROR_REPORTING_HIDE;
  }

  // If there is no container or if it has no config.factory service, we are
  // possibly in an edge-case error situation while trying to serve a regular
  // request on a public site, so use the non-verbose default value.
  return $error_level ?: ERROR_REPORTING_DISPLAY_ALL;
}
