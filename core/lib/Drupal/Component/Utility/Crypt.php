<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Crypt.
 */

namespace Drupal\Component\Utility;

/**
 * Utility class for cryptographically-secure string handling routines.
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
    static $random_state, $bytes;
    // Initialize on the first call. The contents of $_SERVER includes a mix of
    // user-specific and system information that varies a little with each page.
    // Further initialize with the somewhat random PHP process ID.
    if (!isset($random_state)) {
      $random_state = print_r($_SERVER, TRUE) . getmypid();
      $bytes = '';
    }
    if (strlen($bytes) < $count) {
      // /dev/urandom is available on many *nix systems and is considered the
      // best commonly available pseudo-random source.
      if ($fh = @fopen('/dev/urandom', 'rb')) {
        // PHP only performs buffered reads, so in reality it will always read
        // at least 4096 bytes. Thus, it costs nothing extra to read and store
        // that much so as to speed any additional invocations.
        $bytes .= fread($fh, max(4096, $count));
        fclose($fh);
      }
      // openssl_random_pseudo_bytes() will find entropy in a system-dependent
      // way.
      elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes .= openssl_random_pseudo_bytes($count - strlen($bytes));
      }
      // If /dev/urandom is not available or returns no bytes, this loop will
      // generate a good set of pseudo-random bytes on any system.
      // Note that it may be important that our $random_state is passed
      // through hash() prior to being rolled into $output, that the two hash()
      // invocations are different, and that the extra input into the first one -
      // the microtime() - is prepended rather than appended. This is to avoid
      // directly leaking $random_state via the $output stream, which could
      // allow for trivial prediction of further "random" numbers.
      while (strlen($bytes) < $count) {
        $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
        $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
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
    return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
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
    return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
  }

  /**
   * Generates a random, base-64 encoded, URL-safe, sha-256 hashed string.
   *
   * @param int $count
   *   The number of characters (bytes) of the string to be hashed.
   *
   * @return string
   *   A base-64 encoded sha-256 hash, with + replaced with -, / with _ and
   *   any = padding characters removed.
   *
   * @see \Drupal\Component\Utility\Crypt::randomBytes()
   * @see \Drupal\Component\Utility\Crypt::hashBase64()
   */
  public static function randomStringHashed($count) {
    return static::hashBase64(static::randomBytes($count));
  }

}
