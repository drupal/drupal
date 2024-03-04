<?php

namespace Drupal\user;

/**
 * An interface for validating user authentication credentials.
 */
interface UserAuthInterface {

  /**
   * Validates user authentication credentials.
   *
   * @param string $username
   *   The user name to authenticate.
   * @param string $password
   *   A plain-text password, such as trimmed text from form values.
   *
   * @return int|bool
   *   The user's uid on success, or FALSE on failure to authenticate.
   */
  public function authenticate($username, #[\SensitiveParameter] $password);

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
