<?php

namespace Drupal\Core\Session;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event fired when an account is set for the current session.
 */
final class AccountSetEvent extends Event {

  /**
   * The set account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * AccountSetEvent constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The set account.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
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
