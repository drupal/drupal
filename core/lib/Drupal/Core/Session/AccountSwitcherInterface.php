<?php

/**
 * @file
 * Contains \Drupal\Core\Session\AccountSwitcherInterface.
 */

namespace Drupal\Core\Session;

/**
 * Defines an interface for a service for safe account switching.
 *
 * @ingroup user_api
 */
interface AccountSwitcherInterface {

  /**
   * Safely switches to another account.
   *
   * Each invocation of AccountSwitcherInterface::switchTo() must be
   * matched by a corresponding invocation of
   * AccountSwitcherInterface::switchBack() in the same function.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to switch to.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   $this.
   */
  public function switchTo(AccountInterface $account);

  /**
   * Reverts to a previous account after switching.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   $this.
   *
   * @throws \RuntimeException
   *   When there are no more account switches to revert.
   */
  public function switchBack();

}
