<?php

namespace Drupal\Core\Password;

/**
 * Secure password hashing functions for user authentication.
 */
interface PasswordInterface {

  /**
   * Maximum password length.
   */
  const PASSWORD_MAX_LENGTH = 512;

  /**
   * Hash a password using a secure hash.
   *
   * @param string $password
   *   A plain-text password.
   *
   * @return string
   *   A string containing the hashed password, or FALSE on failure.
   */
  public function hash(#[\SensitiveParameter] $password);

  /**
   * Check whether a plain text password matches a hashed password.
   *
   * @param string $password
   *   A plain-text password.
   * @param string|null $hash
   *   A hashed password.
   *
   * @return bool
   *   TRUE if the password is valid, FALSE if not.
   */
  public function check(#[\SensitiveParameter] $password, #[\SensitiveParameter] $hash);

  /**
   * Check whether a hashed password needs to be replaced with a new hash.
   *
   * This is typically called during the login process in order to trigger the
   * rehashing of the password, as in that stage, the plain text password is
   * available.
   *
   * This method returns TRUE if the password was hashed with an older
   * algorithm.
   *
   * @param string|null $hash
   *   The hash to be checked.
   *
   * @return bool
   *   TRUE if the hash is outdated and needs rehash.
   */
  public function needsRehash(#[\SensitiveParameter] $hash);

}
