<?php

/**
 * @file
 * Definition of Drupal\Core\Password\PasswordInterface
 */

namespace Drupal\Core\Password;

use Drupal\user\UserInterface;

/**
 * Secure password hashing functions for user authentication.
 */
interface PasswordInterface {

  /**
   * Hash a password using a secure hash.
   *
   * @param string $password
   *   A plain-text password.
   *
   * @return string
   *   A string containing the hashed password (and a salt), or FALSE on failure.
   */
  public function hash($password);

  /**
   * Check whether a plain text password matches a stored hashed password.
   *
   * Alternative implementations of this function may use other data in the
   * $account object, for example the uid to look up the hash in a custom table
   * or remote database.
   *
   * @param string $password
   *   A plain-text password
   * @param \Drupal\user\UserInterface $account
   *   A user entity.
   *
   * @return bool
   *   TRUE if the password is valid, FALSE if not.
   */
  public function check($password, UserInterface $account);

  /**
   * Check whether a user's hashed password needs to be replaced with a new hash.
   *
   * This is typically called during the login process when the plain text
   * password is available. A new hash is needed when the desired iteration
   * count has changed by a modification of the password-service in the
   * dependency injection container or if the user's password hash was
   * generated in an update like user_update_7000() (see the Drupal 7
   * documentation).
   *
   * Alternative implementations of this function might use other criteria based
   * on the fields in $account.
   *
   * @param \Drupal\user\UserInterface $account
   *   A user entity.
   *
   * @return boolean
   *   TRUE or FALSE.
   */
  public function userNeedsNewHash(UserInterface $account);

}
