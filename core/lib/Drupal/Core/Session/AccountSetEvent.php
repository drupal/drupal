<?php

namespace Drupal\Core\Session;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event fired when an account is set for the current session.
 */
final class AccountSetEvent extends Event {

  /**
   * AccountSetEvent constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The set account.
   */
  public function __construct(
    protected AccountInterface $account,
  ) {
  }

  /**
   * Gets the account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account.
   */
  public function getAccount() {
    return $this->account;
  }

}
