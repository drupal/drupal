<?php

namespace Drupal\Component\Utility;

/**
 * Utility class for cryptographically-secure string handling routines.
 *
 * @ingroup utility
 */
class Crypt {

  /**
   * Returns a string of highly randomized bytes (over the full 8-bit range).
   *
   * This function is better than simply calling mt_rand() or any other built-in
   * PHP function because it can return a long string of bytes (compared to < 4
   * bytes normally from mt_rand()) and uses the best available pseudo-random
   * source.
   *
   * In PHP 7 and up, this uses the built-in PHP function random_bytes().
   * In older PHP versions, this uses the random_bytes() function provided by
   * the random_compat library, or the fallback hash-based generator from Drupal
   * 7.x.
   *
   * @param int $count
   *   The number of characters (bytes) to return in the string.
   *
   * @return string
   *   A randomly generated string.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
   *   Use PHP's built-in random_bytes() function instead.
   *
   * @see https://www.drupal.org/node/3057191
   */
  public static function randomBytes($count) {
    @trigger_error(__CLASS__ . '::randomBytes() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use PHP\'s built-in random_bytes() function instead. See https://www.drupal.org/node/3057191', E_USER_DEPRECATED);
    return random_bytes($count);
  }

  /**
   * Calculates a base-64 encoded, URL-safe sha-256 hmac.
   *
   * @param mixed $data
   *   Scalar value to be validated with the hmac.
   * @param mixed $key
   *   A secret key, this can be any scalar value.
   *
   * @return string
   *   A base-64 encoded sha-256 hmac, with + replaced with -, / with _ and
   *   any = padding characters removed.
   */
  public static function hmacBase64($data, $key) {
    // $data and $key being strings here is necessary to avoid empty string
    // results of the hash function if they are not scalar values. As this
    // function is used in security-critical contexts like token validation it
    // is important that it never returns an empty string.
    if (!is_scalar($data) || !is_scalar($key)) {
      throw new \InvalidArgumentException('Both parameters passed to \Drupal\Component\Utility\Crypt::hmacBase64 must be scalar values.');
    }

    $hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));
    // Modify the hmac so it's safe to use in URLs.
    return str_replace(['+', '/', '='], ['-', '_', ''], $hmac);
  }

  /**
   * Calculates a base-64 encoded, URL-safe sha-256 hash.
   *
   * @param string $data
   *   String to be hashed.
   *
   * @return string
   *   A base-64 encoded sha-256 hash, with + replaced with -, / with _ and
   *   any = padding characters removed.
   */
  public static function hashBase64($data) {
    $hash = base64_encode(hash('sha256', $data, TRUE));
    // Modify the hash so it's safe to use in URLs.
    return str_replace(['+', '/', '='], ['-', '_', ''], $hash);
  }

  /**
   * Compares strings in constant time.
   *
   * @param string $known_string
   *   The expected string.
   * @param string $user_string
   *   The user supplied string to check.
   *
   * @return bool
   *   Returns TRUE when the two strings are equal, FALSE otherwise.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
   *   Use PHP's built-in hash_equals() function instead.
   *
   * @see https://www.drupal.org/node/3054488
   */
  public static function hashEquals($known_string, $user_string) {
    @trigger_error(__CLASS__ . '::hashEquals() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use PHP\'s built-in hash_equals() function instead. See https://www.drupal.org/node/3054488', E_USER_DEPRECATED);
    return hash_equals($known_string, $user_string);
  }

  /**
   * Returns a URL-safe, base64 encoded string of highly randomized bytes.
   *
   * @param $count
   *   The number of random bytes to fetch and base64 encode.
   *
   * @return string
   *   The base64 encoded result will have a length of up to 4 * $count.
   *
   * @see \Drupal\Component\Utility\Crypt::randomBytes()
   */
  public static function randomBytesBase64($count = 32) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes($count)));
  }

}
