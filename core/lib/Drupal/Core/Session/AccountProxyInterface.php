<?php

/**
 * @file
 * Contains \Drupal\Core\Session\AccountProxyInterface.
 */

namespace Drupal\Core\Session;

/**
 * Defines an interface for a service which has the current account stored.
 */
interface AccountProxyInterface extends AccountInterface {

  /**
   * Set the current wrapped account.
   *
   * Setting the current account is highly discouraged! Instead, make sure to
   * inject the desired user object into the dependent code directly
   *
   * @param \Drupal\Core\Session\AccountInterface
   *   The current account.
   */
  public function setAccount(AccountInterface $account);

  /**
   * Set the current wrapped account.
   *
   * Setting the current account is highly discouraged! Instead, make sure to
   * inject the desired user object into the dependent code directly
   *
   * @param \Drupal\Core\Session\AccountInterface
   *   The current account.
   */
  public function getAccount();

}

