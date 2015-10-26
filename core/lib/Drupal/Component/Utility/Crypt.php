<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Crypt.
 */

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
   * @param int $count
   *   The number of characters (bytes) to return in the string.
   *
   * @return string
   *   A randomly generated string.
   */
  public static function randomBytes($count) {
    // $random_state does not use drupal_static as it stores random bytes.
    static $random_state, $bytes;

    $missing_bytes = $count - strlen($bytes);

    if ($missing_bytes > 0) {
      // openssl_random_pseudo_bytes() will find entropy in a system-dependent
      // way.
      if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes .= openssl_random_pseudo_bytes($missing_bytes);
      }

      // If OpenSSL is not available, we can use mcrypt. On Windows, this will
      // transparently pull from CryptGenRandom. On Unix-based systems, it will
      // read from /dev/urandom as expected.
      elseif (function_exists(('mcrypt_create_iv')) && defined('MCRYPT_DEV_URANDOM')) {
        $bytes .= mcrypt_create_iv($count, MCRYPT_DEV_URANDOM);
      }

      // Else, read directly from /dev/urandom, which is available on many *nix
      // systems and is considered cryptographically secure.
      elseif ($fh = @fopen('/dev/urandom', 'rb')) {
        // PHP only performs buffered reads, so in reality it will always read
        // at least 4096 bytes. Thus, it costs nothing extra to read and store
        // that much so as to speed any additional invocations.
        $bytes .= fread($fh, max(4096, $missing_bytes));
        fclose($fh);
      }

      // If we couldn't get enough entropy, this simple hash-based PRNG will
      // generate a good set of pseudo-random bytes on any system.
      // Note that it may be important that our $random_state is passed
      // through hash() prior to being rolled into $output, that the two hash()
      // invocations are different, and that the extra input into the first one -
      // the microtime() - is prepended rather than appended. This is to avoid
      // directly leaking $random_state via the $output stream, which could
      // allow for trivial prediction of further "random" numbers.
      if (strlen($bytes) < $count) {
        // Initialize on the first call. The contents of $_SERVER includes a mix
        // of user-specific and system information that varies a little with
        // each page.
        if (!isset($random_state)) {
          $random_state = print_r($_SERVER, TRUE);
          if (function_exists('getmypid')) {
            // Further initialize with the somewhat random PHP process ID.
            $random_state .= getmypid();
          }
          $bytes = '';
        }

        do {
          $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
          $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
        } while (strlen($bytes) < $count);
      }
    }
    $output = substr($bytes, 0, $count);
    $bytes = substr($bytes, $count);
    return $output;
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
   */
  public static function hashEquals($known_string, $user_string) {
    if (function_exists('hash_equals')) {
      return hash_equals($known_string, $user_string);
    }
    else {
      // Backport of hash_equals() function from PHP 5.6
      // @see https://github.com/php/php-src/blob/PHP-5.6/ext/hash/hash.c#L739
      if (!is_string($known_string)) {
        trigger_error(sprintf("Expected known_string to be a string, %s given", gettype($known_string)), E_USER_WARNING);
        return FALSE;
      }

      if (!is_string($user_string)) {
        trigger_error(sprintf("Expected user_string to be a string, %s given", gettype($user_string)), E_USER_WARNING);
        return FALSE;
      }

      $known_len = strlen($known_string);
      if ($known_len !== strlen($user_string)) {
        return FALSE;
      }

      // This is security sensitive code. Do not optimize this for speed.
      $result = 0;
      for ($i = 0; $i < $known_len; $i++) {
        $result |= (ord($known_string[$i]) ^ ord($user_string[$i]));
      }

      return $result === 0;
    }
  }

  /**
   * Returns a URL-safe, base64 encoded string of highly randomized bytes.
   *
   * @param $byte_count
   *   The number of random bytes to fetch and base64 encode.
   *
   * @return string
   *   The base64 encoded result will have a length of up to 4 * $byte_count.
   *
   * @see \Drupal\Component\Utility\Crypt::randomBytes()
   */
  public static function randomBytesBase64($count = 32) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(static::randomBytes($count)));
  }

}
