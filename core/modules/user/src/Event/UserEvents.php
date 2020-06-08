<?php

namespace Drupal\user\Event;

/**
 * Defines events for the user module.
 */
final class UserEvents {

  /**
   * The name of the event fired when a login is blocked by flood control.
   *
   * This event allows modules to perform an action whenever flood control has
   * been triggered by excessive login attempts for a particular user account.
   * The event listener method receives a \Drupal\user\Event\UserFloodEvent
   * instance.
   *
   * @Event
   *
   * @see: \Drupal\user\UserFloodControl::isAllowed
   * @see: \Drupal\user\EventSubscriber\UserFloodSubscriber
   *
   * @var string
   */
  const FLOOD_BLOCKED_USER = 'user.flood_blocked_user';

  /**
   * The name of the event fired when a login is blocked by flood control.
   *
   * This event allows modules to perform an action whenever flood control has
   * been triggered by excessive login attempts from a particular IP. The event
   * listener method receives a \Drupal\user\Event\UserFloodEvent instance.
   *
   * @Event
   *
   * @see: \Drupal\user\UserFloodControl::isAllowed
   * @see: \Drupal\user\EventSubscriber\UserFloodSubscriber
   *
   * @var string
   */
  const FLOOD_BLOCKED_IP = 'user.flood_blocked_ip';

}
