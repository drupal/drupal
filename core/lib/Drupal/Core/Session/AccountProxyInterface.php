<?php

namespace Drupal\Core\Session;

/**
 * Defines an interface for a service which has the current account stored.
 *
 * It is generally more useful to use \Drupal\Core\Session\AccountInterface
 * unless one specifically needs the proxying features of this interface.
 *
 * @see \Drupal\Core\Session\AccountInterface
 *
 * @ingroup user_api
 */
interface AccountProxyInterface extends AccountInterface {

  /**
   * Sets the currently wrapped account.
   *
   * Setting the current account is highly discouraged! Instead, make sure to
   * inject the desired user object into the dependent code directly.
   *
   * A preferable method of account impersonation is to use
   * \Drupal\Core\Session\AccountSwitcherInterface::switchTo() and
   * \Drupal\Core\Session\AccountSwitcherInterface::switchBack().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function setAccount(AccountInterface $account);

  /**
   * Gets the currently wrapped account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current account.
   */
  public function getAccount();

  /**
   * Sets the id of the initial account.
   *
   * Never use this method, its sole purpose is to work around weird effects
   * during mid-request container rebuilds.
   *
   * @param int $account_id
   *   The id of the initial account.
   */
  public function setInitialAccountId($account_id);

}
