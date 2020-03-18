<?php

/**
 * @file
 * Contains code for sanitizing user input from the request.
 */

/**
 * Sanitizes user input from the request.
 */
class DrupalRequestSanitizer {

  /**
   * Tracks whether the request was already sanitized.
   */
  protected static $sanitized = FALSE;

  /**
   * Modifies the request to strip dangerous keys from user input.
   */
  public static function sanitize() {
    if (!self::$sanitized) {
      $whitelist = variable_get('sanitize_input_whitelist', array());
      $log_sanitized_keys = variable_get('sanitize_input_logging', FALSE);

      // Process query string parameters.
      $get_sanitized_keys = array();
      $_GET = self::stripDangerousValues($_GET, $whitelist, $get_sanitized_keys);
      if ($log_sanitized_keys && $get_sanitized_keys) {
        _drupal_trigger_error_with_delayed_logging(format_string('Potentially unsafe keys removed from query string parameters (GET): @keys', array('@keys' => implode(', ', $get_sanitized_keys))), E_USER_NOTICE);
      }

      // Process request body parameters.
      $post_sanitized_keys = array();
      $_POST = self::stripDangerousValues($_POST, $whitelist, $post_sanitized_keys);
      if ($log_sanitized_keys && $post_sanitized_keys) {
        _drupal_trigger_error_with_delayed_logging(format_string('Potentially unsafe keys removed from request body parameters (POST): @keys', array('@keys' => implode(', ', $post_sanitized_keys))), E_USER_NOTICE);
      }

      // Process cookie parameters.
      $cookie_sanitized_keys = array();
      $_COOKIE = self::stripDangerousValues($_COOKIE, $whitelist, $cookie_sanitized_keys);
      if ($log_sanitized_keys && $cookie_sanitized_keys) {
        _drupal_trigger_error_with_delayed_logging(format_string('Potentially unsafe keys removed from cookie parameters (COOKIE): @keys', array('@keys' => implode(', ', $cookie_sanitized_keys))), E_USER_NOTICE);
      }

      $request_sanitized_keys = array();
      $_REQUEST = self::stripDangerousValues($_REQUEST, $whitelist, $request_sanitized_keys);

      self::$sanitized = TRUE;
    }
  }

  /**
   * Removes the destination if it is dangerous.
   *
   * Note this can only be called after common.inc has been included.
   *
   * @return bool
   *   TRUE if the destination has been removed from $_GET, FALSE if not.
   */
  public static function cleanDestination() {
    $dangerous_keys = array();
    $log_sanitized_keys = variable_get('sanitize_input_logging', FALSE);

    $parts = drupal_parse_url($_GET['destination']);
    // If there is a query string, check its query parameters.
    if (!empty($parts['query'])) {
      $whitelist = variable_get('sanitize_input_whitelist', array());

      self::stripDangerousValues($parts['query'], $whitelist, $dangerous_keys);
      if (!empty($dangerous_keys)) {
        // The destination is removed rather than sanitized to mirror the
        // handling of external destinations.
        unset($_GET['destination']);
        unset($_REQUEST['destination']);
        if ($log_sanitized_keys) {
          trigger_error(format_string('Potentially unsafe destination removed from query string parameters (GET) because it contained the following keys: @keys', array('@keys' => implode(', ', $dangerous_keys))));
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Strips dangerous keys from the provided input.
   *
   * @param mixed $input
   *   The input to sanitize.
   * @param string[] $whitelist
   *   An array of keys to whitelist as safe.
   * @param string[] $sanitized_keys
   *   An array of keys that have been removed.
   *
   * @return mixed
   *   The sanitized input.
   */
  protected static function stripDangerousValues($input, array $whitelist, array &$sanitized_keys) {
    if (is_array($input)) {
      foreach ($input as $key => $value) {
        if ($key !== '' && is_string($key) && $key[0] === '#' && !in_array($key, $whitelist, TRUE)) {
          unset($input[$key]);
          $sanitized_keys[] = $key;
        }
        else {
          $input[$key] = self::stripDangerousValues($input[$key], $whitelist, $sanitized_keys);
        }
      }
    }
    return $input;
  }

}
