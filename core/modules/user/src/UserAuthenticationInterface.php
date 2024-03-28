<?php

namespace Drupal\user;

/**
 * An interface for validating user authentication credentials.
 */
interface UserAuthenticationInterface {

  /**
   * Validates user authentication credentials.
   *
   * @param string $identifier
   *   The user identifier to authenticate. Usually the username.
   *
   * @return Drupal\User\UserInterface|false
   *   The user account on success, or FALSE on failure to authenticate.
   */
  public function lookupAccount($identifier): UserInterface|false;

  /**
   * Validates user authentication credentials for an account.
   *
   * This can be used where the account has already been located using the login
   * credentials.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to authenticate.
   * @param string $password
   *   A plain-text password, such as trimmed text from form values.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function authenticateAccount(UserInterface $account, #[\SensitiveParameter] string $password): bool;

}
