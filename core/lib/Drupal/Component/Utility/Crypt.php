<?php

namespace Drupal\Component\Utility;

/**
 * Utility class for cryptographically-secure string handling routines.
 *
 * @ingroup utility
 */
class Crypt {

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
   * Returns a URL-safe, base64 encoded string of highly randomized bytes.
   *
   * @param $count
   *   The number of random bytes to fetch and base64 encode.
   *
   * @return string
   *   The base64 encoded result will have a length of up to 4 * $count.
   */
  public static function randomBytesBase64($count = 32) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes($count)));
  }

}
