<?php

namespace Drupal\Core\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Sanitizes user input.
 */
class RequestSanitizer {

  /**
   * Request attribute to mark the request as sanitized.
   */
  const SANITIZED = '_drupal_request_sanitized';

  /**
   * The name of the setting that configures the whitelist.
   */
  const SANITIZE_WHITELIST = 'sanitize_input_whitelist';

  /**
   * The name of the setting that determines if sanitized keys are logged.
   */
  const SANITIZE_LOG = 'sanitize_input_logging';

  /**
   * Strips dangerous keys from user input.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request to sanitize.
   * @param string[] $whitelist
   *   An array of keys to whitelist as safe. See default.settings.php.
   * @param bool $log_sanitized_keys
   *   (optional) Set to TRUE to log an keys that are sanitized.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The sanitized request.
   */
  public static function sanitize(Request $request, $whitelist, $log_sanitized_keys = FALSE) {
    if (!$request->attributes->get(self::SANITIZED, FALSE)) {
      // Process query string parameters.
      $get_sanitized_keys = [];
      $request->query->replace(static::stripDangerousValues($request->query->all(), $whitelist, $get_sanitized_keys));
      if ($log_sanitized_keys && !empty($get_sanitized_keys)) {
        trigger_error(sprintf('Potentially unsafe keys removed from query string parameters (GET): %s', implode(', ', $get_sanitized_keys)));
      }

      // Request body parameters.
      $post_sanitized_keys = [];
      $request->request->replace(static::stripDangerousValues($request->request->all(), $whitelist, $post_sanitized_keys));
      if ($log_sanitized_keys && !empty($post_sanitized_keys)) {
        trigger_error(sprintf('Potentially unsafe keys removed from request body parameters (POST): %s', implode(', ', $post_sanitized_keys)));
      }

      // Cookie parameters.
      $cookie_sanitized_keys = [];
      $request->cookies->replace(static::stripDangerousValues($request->cookies->all(), $whitelist, $cookie_sanitized_keys));
      if ($log_sanitized_keys && !empty($cookie_sanitized_keys)) {
        trigger_error(sprintf('Potentially unsafe keys removed from cookie parameters: %s', implode(', ', $cookie_sanitized_keys)));
      }

      if (!empty($get_sanitized_keys) || !empty($post_sanitized_keys) || !empty($cookie_sanitized_keys)) {
        $request->overrideGlobals();
      }
      $request->attributes->set(self::SANITIZED, TRUE);
    }
    return $request;
  }

  /**
   * Strips dangerous keys from $input.
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
        if ($key !== '' && $key[0] === '#' && !in_array($key, $whitelist, TRUE)) {
          unset($input[$key]);
          $sanitized_keys[] = $key;
        }
        else {
          $input[$key] = static::stripDangerousValues($input[$key], $whitelist, $sanitized_keys);
        }
      }
    }
    return $input;
  }

}
