<?php

namespace Drupal\Core\Session;

/**
 * Defines events for the account system.
 *
 * @see \Drupal\Core\Session\AccountSetEvent
 */
final class AccountEvents {

  /**
   * Name of the event fired when the current user is set.
   *
   * This event allows modules to perform an action whenever the current user is
   * set. The event listener receives an \Drupal\Core\Session\AccountSetEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Session\AccountSetEvent
   * @see \Drupal\Core\Session\AccountProxyInterface::setAccount()
   *
   * @var string
   */
  const SET_USER = 'account.set';

}
