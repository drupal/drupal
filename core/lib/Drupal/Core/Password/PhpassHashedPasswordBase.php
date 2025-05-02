<?php

namespace Drupal\Core\Password;

// cspell:ignore ITOA64

/**
 * Legacy password hashing framework.
 *
 * @internal
 *
 * @see https://www.drupal.org/node/3322420
 */
abstract class PhpassHashedPasswordBase implements PasswordInterface {

  /**
   * The minimum allowed log2 number of iterations for password stretching.
   */
  const MIN_HASH_COUNT = 7;

  /**
   * The maximum allowed log2 number of iterations for password stretching.
   */
  const MAX_HASH_COUNT = 30;

  /**
   * The expected (and maximum) number of characters in a hashed password.
   */
  const HASH_LENGTH = 55;

  /**
   * Returns a string for mapping an int to the corresponding base 64 character.
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public static $ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  /**
   * Constructs a new password hashing instance.
   *
   * @param \Drupal\Core\Password\PasswordInterface $corePassword
   *   The core PHP password interface.
   */
  public function __construct(protected PasswordInterface $corePassword) {
  }

  /**
   * Encodes bytes into printable base 64 using the *nix standard from crypt().
   *
   * @param string $input
   *   The string containing bytes to encode.
   * @param int $count
   *   The number of characters (bytes) to encode.
   *
   * @return string
   *   Encoded string.
   */
  protected function base64Encode($input, $count) {
    $output = '';
    $i = 0;
    do {
      $value = ord($input[$i++]);
      $output .= static::$ITOA64[$value & 0x3f];
      if ($i < $count) {
        $value |= ord($input[$i]) << 8;
      }
      $output .= static::$ITOA64[($value >> 6) & 0x3f];
      if ($i++ >= $count) {
        break;
      }
      if ($i < $count) {
        $value |= ord($input[$i]) << 16;
      }
      $output .= static::$ITOA64[($value >> 12) & 0x3f];
      if ($i++ >= $count) {
        break;
      }
      $output .= static::$ITOA64[($value >> 18) & 0x3f];
    } while ($i < $count);

    return $output;
  }

  /**
   * Ensures that $count_log2 is within set bounds.
   *
   * @param int $count_log2
   *   Integer that determines the number of iterations used in the hashing
   *   process. A larger value is more secure, but takes more time to complete.
   *
   * @return int
   *   Integer within set bounds that is closest to $count_log2.
   */
  protected function enforceLog2Boundaries($count_log2) {
    if ($count_log2 < static::MIN_HASH_COUNT) {
      return static::MIN_HASH_COUNT;
    }
    elseif ($count_log2 > static::MAX_HASH_COUNT) {
      return static::MAX_HASH_COUNT;
    }

    return (int) $count_log2;
  }

  /**
   * Hash a password using a secure stretched hash.
   *
   * By using a salt and repeated hashing the password is "stretched". Its
   * security is increased because it becomes much more computationally costly
   * for an attacker to try to break the hash by brute-force computation of the
   * hashes of a large number of plain-text words or strings to find a match.
   *
   * @param string $algo
   *   The string name of a hashing algorithm usable by hash(), like 'sha256'.
   * @param string $password
   *   Plain-text password up to 512 bytes (128 to 512 UTF-8 characters) to
   *   hash.
   * @param string $setting
   *   An existing hash or the output of $this->generateSalt(). Must be at least
   *   12 characters (the settings and salt).
   *
   * @return string
   *   A string containing the hashed password (and salt) or FALSE on failure.
   *   The return string will be truncated at HASH_LENGTH characters max.
   */
  protected function crypt($algo, #[\SensitiveParameter] $password, $setting) {
    // Prevent DoS attacks by refusing to hash large passwords.
    if (strlen($password) > PasswordInterface::PASSWORD_MAX_LENGTH) {
      return FALSE;
    }

    // The first 12 characters of an existing hash are its setting string.
    $setting = substr($setting, 0, 12);

    if ($setting[0] != '$' || $setting[2] != '$') {
      return FALSE;
    }
    $count_log2 = $this->getCountLog2($setting);
    // Stored hashes may have been encrypted with any iteration count. However
    // we do not allow applying the algorithm for unreasonable low and high
    // values respectively.
    if ($count_log2 != $this->enforceLog2Boundaries($count_log2)) {
      return FALSE;
    }
    $salt = substr($setting, 4, 8);
    // Hashes must have an 8 character salt.
    if (strlen($salt) != 8) {
      return FALSE;
    }

    // Convert the base 2 logarithm into an integer.
    $count = 1 << $count_log2;

    $hash = hash($algo, $salt . $password, TRUE);
    do {
      $hash = hash($algo, $hash . $password, TRUE);
    } while (--$count);

    $len = strlen($hash);
    $output = $setting . $this->base64Encode($hash, $len);
    // $this->base64Encode() of a 16 byte MD5 will always be 22 characters.
    // $this->base64Encode() of a 64 byte sha512 will always be 86 characters.
    $expected = 12 + ceil((8 * $len) / 6);
    return (strlen($output) == $expected) ? substr($output, 0, static::HASH_LENGTH) : FALSE;
  }

  /**
   * Parses the log2 iteration count from a stored hash or setting string.
   *
   * @param string $setting
   *   An existing hash or the output of $this->generateSalt(). Must be at least
   *   12 characters (the settings and salt).
   *
   * @return int
   *   The log2 iteration count.
   */
  public function getCountLog2($setting) {
    return strpos(static::$ITOA64, $setting[3]);
  }

  /**
   * {@inheritdoc}
   */
  public function hash(#[\SensitiveParameter] $password) {
    return $this->corePassword->hash($password);
  }

  /**
   * {@inheritdoc}
   */
  public function check(#[\SensitiveParameter] $password, #[\SensitiveParameter] $hash) {
    // Newly created accounts may have empty passwords.
    if ($hash === NULL || $hash === '') {
      return FALSE;
    }
    if (str_starts_with($hash, 'U$')) {
      // This may be an updated password from user_update_7000(). Such hashes
      // have 'U' added as the first character and need an extra md5() (see the
      // Drupal 7 documentation).
      $stored_hash = substr($hash, 1);
      $password = md5($password);
    }
    else {
      $stored_hash = $hash;
    }

    $type = substr($stored_hash, 0, 3);
    switch ($type) {
      case '$S$':
        // A normal Drupal 7 password using sha512.
        $computed_hash = $this->crypt('sha512', $password, $stored_hash);
        break;

      case '$H$':
        // phpBB3 uses "$H$" for the same thing as "$P$".
      case '$P$':
        // A phpass password generated using md5.  This is an
        // imported password or from an earlier Drupal version.
        $computed_hash = $this->crypt('md5', $password, $stored_hash);
        break;

      default:
        return $this->corePassword->check($password, $stored_hash);

    }

    // Compare using hash_equals() instead of === to mitigate timing attacks.
    return $computed_hash && hash_equals($stored_hash, $computed_hash);
  }

  /**
   * {@inheritdoc}
   */
  public function needsRehash(#[\SensitiveParameter] $hash) {
    return $this->corePassword->needsRehash($hash);
  }

}
