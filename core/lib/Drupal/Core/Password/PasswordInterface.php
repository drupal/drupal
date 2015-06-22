<?php

/**
 * @file
 * Contains \Drupal\Core\Password\PasswordInterface.
 */

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
  public function hash($password);

  /**
   * Check whether a plain text password matches a hashed password.
   *
   * @param string $password
   *   A plain-text password
   * @param string $hash
   *   A hashed password.
   *
   * @return bool
   *   TRUE if the password is valid, FALSE if not.
   */
  public function check($password, $hash);

  /**
   * Check whether a hashed password needs to be replaced with a new hash.
   *
   * This is typically called during the login process when the plain text
   * password is available. A new hash is needed when the desired iteration
   * count has changed by a modification of the password-service in the
   * dependency injection container or if the user's password hash was
   * generated in an update like user_update_7000() (see the Drupal 7
   * documentation).
   *
   * @param string $hash
   *   The existing hash to be checked.
   *
   * @return bool
   *   TRUE if the hash is outdated and needs rehash.
   */
  public function needsRehash($hash);

}
