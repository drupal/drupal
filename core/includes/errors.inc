<?php

/**
 * @file
 * Functions for error handling.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\RfcLogLevel;
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
    _drupal_log_error(array(
      '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
      // The standard PHP error handler considers that the error messages
      // are HTML. We mimick this behavior here.
      '!message' => Xss::filterAdmin($message),
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => $severity_level,
      'backtrace' => $backtrace,
    ), $error_level == E_RECOVERABLE_ERROR);
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
 *   An array with the following keys: %type, !message, %function, %file,
 *   %line, severity_level, and backtrace. All the parameters are plain-text,
 *   with the exception of !message, which needs to be a safe HTML string, and
 *   backtrace, which is a standard PHP backtrace.
 * @param $fatal
 *   TRUE if the error is fatal.
 */
function _drupal_log_error($error, $fatal = FALSE) {
  $is_installer = drupal_installation_attempted();
  // Initialize a maintenance theme if the bootstrap was not complete.
  // Do it early because drupal_set_message() triggers a
  // \Drupal\Core\Theme\ThemeManager::initTheme().
  if ($fatal && \Drupal::hasService('theme.manager')) {
    // The installer initializes a maintenance theme at the earliest possible
    // point in time already. Do not unset that.
    if (!$is_installer) {
      \Drupal::theme()->resetActiveTheme();
    }
    if (!defined('MAINTENANCE_MODE')) {
      define('MAINTENANCE_MODE', 'error');
    }
    // No-op if the active theme is set already.
    drupal_maintenance_theme();
  }

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
      $error['!message'],
      $error['%type'],
      array(
        'function' => $error['%function'],
        'file' => $error['%file'],
        'line' => $error['%line'],
      ),
    );
    header('X-Drupal-Assertion-' . $number . ': ' . rawurlencode(serialize($assertion)));
    $number++;
  }

  // Only call the logger if there is a logger factory available. This can occur
  // if there is an error while rebuilding the container or during the
  // installer.
  if (\Drupal::hasService('logger.factory')) {
    \Drupal::logger('php')->log($error['severity_level'], '%type: !message in %function (line %line of %file).', $error);
  }

  if (PHP_SAPI === 'cli') {
    if ($fatal) {
      // When called from CLI, simply output a plain text message.
      // Should not translate the string to avoid errors producing more errors.
      print html_entity_decode(strip_tags(format_string('%type: !message in %function (line %line of %file).', $error))). "\n";
      exit;
    }
  }

  if (\Drupal::hasRequest() && \Drupal::request()->isXmlHttpRequest()) {
    if ($fatal) {
      if (error_displayable($error)) {
        // When called from JavaScript, simply output the error message.
        // Should not translate the string to avoid errors producing more errors.
        print format_string('%type: !message in %function (line %line of %file).', $error);
      }
      exit;
    }
  }
  else {
    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionally in update.php.
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
      // Should not translate the string to avoid errors producing more errors.
      $message = format_string('%type: !message in %function (line %line of %file).', $error);

      // Check if verbose error reporting is on.
      $error_level = _drupal_get_error_level();

      if ($error_level == ERROR_REPORTING_DISPLAY_VERBOSE) {
        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);
        // Generate a backtrace containing only scalar argument values.
        $message .= '<pre class="backtrace">' . Error::formatBacktrace($backtrace) . '</pre>';
      }
      if (\Drupal::hasService('session_manager')) {
        // Message display is dependent on sessions being available.
        drupal_set_message(SafeMarkup::set($message), $class, TRUE);
      }
      else {
        print $message;
      }
    }

    if ($fatal) {
      // We fallback to a maintenance page at this point, because the page generation
      // itself can generate errors.
      // Should not translate the string to avoid errors producing more errors.
      $message = 'The website encountered an unexpected error. Please try again later.';
      if ($is_installer) {
        // install_display_output() prints the output and ends script execution.
        $output = array(
          '#title' => 'Error',
          '#markup' => $message,
        );
        install_display_output($output, $GLOBALS['install_state']);
      }
      else {
        $output = \Drupal::service('bare_html_page_renderer')->renderBarePage(['#markup' => $message], 'Error', 'maintenance_page');
      }

      $response = new Response($output, 500);
      $response->setStatusCode(500, '500 Service unavailable (with message)');
      // An exception must halt script execution.
      $response->send();
      exit;
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
  if (\Drupal::hasService('config.factory')) {
    $error_level = \Drupal::config('system.logging')->get('error_level');
  }
  // If there is no container or if it has no config.factory service, we are
  // possibly in an edge-case error situation while trying to serve a regular
  // request on a public site, so use the non-verbose default value.
  return $error_level ?: ERROR_REPORTING_DISPLAY_ALL;
}
