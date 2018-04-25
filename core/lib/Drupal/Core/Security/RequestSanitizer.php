<?php

namespace Drupal\Core\Security;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
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
      $update_globals = FALSE;
      $bags = [
        'query' => 'Potentially unsafe keys removed from query string parameters (GET): %s',
        'request' => 'Potentially unsafe keys removed from request body parameters (POST): %s',
        'cookies' => 'Potentially unsafe keys removed from cookie parameters: %s',
      ];
      foreach ($bags as $bag => $message) {
        if (static::processParameterBag($request->$bag, $whitelist, $log_sanitized_keys, $bag, $message)) {
          $update_globals = TRUE;
        }
      }
      if ($update_globals) {
        $request->overrideGlobals();
      }
      $request->attributes->set(self::SANITIZED, TRUE);
    }
    return $request;
  }

  /**
   * Processes a request parameter bag.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $bag
   *   The parameter bag to process.
   * @param string[] $whitelist
   *   An array of keys to whitelist as safe.
   * @param bool $log_sanitized_keys
   *   Set to TRUE to log keys that are sanitized.
   * @param string $bag_name
   *   The request parameter bag name. Either 'query', 'request' or 'cookies'.
   * @param string $message
   *   The message to log if the parameter bag contains keys that are removed.
   *   If the message contains %s that is replaced by a list of removed keys.
   *
   * @return bool
   *   TRUE if the parameter bag has been sanitized, FALSE if not.
   */
  protected static function processParameterBag(ParameterBag $bag, $whitelist, $log_sanitized_keys, $bag_name, $message) {
    $sanitized = FALSE;
    $sanitized_keys = [];
    $bag->replace(static::stripDangerousValues($bag->all(), $whitelist, $sanitized_keys));
    if (!empty($sanitized_keys)) {
      $sanitized = TRUE;
      if ($log_sanitized_keys) {
        trigger_error(sprintf($message, implode(', ', $sanitized_keys)));
      }
    }

    if ($bag->has('destination')) {
      $destination_dangerous_keys = static::checkDestination($bag->get('destination'), $whitelist);
      if (!empty($destination_dangerous_keys)) {
        // The destination is removed rather than sanitized because the URL
        // generator service is not available and this method is called very
        // early in the bootstrap.
        $bag->remove('destination');
        $sanitized = TRUE;
        if ($log_sanitized_keys) {
          trigger_error(sprintf('Potentially unsafe destination removed from %s parameter bag because it contained the following keys: %s', $bag_name, implode(', ', $destination_dangerous_keys)));
        }
      }
    }
    return $sanitized;
  }

  /**
   * Checks a destination string to see if it is dangerous.
   *
   * @param string $destination
   *   The destination string to check.
   * @param array $whitelist
   *   An array of keys to whitelist as safe.
   *
   * @return array
   *   The dangerous keys found in the destination parameter.
   */
  protected static function checkDestination($destination, array $whitelist) {
    $dangerous_keys = [];
    $parts = UrlHelper::parse($destination);
    // If there is a query string, check its query parameters.
    if (!empty($parts['query'])) {
      static::stripDangerousValues($parts['query'], $whitelist, $dangerous_keys);
    }
    return $dangerous_keys;
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
