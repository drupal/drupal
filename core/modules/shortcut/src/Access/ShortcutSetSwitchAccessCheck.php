<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\ShortcutSetSwitchAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Checks access to switch a user's shortcut set.
 */
class ShortcutSetSwitchAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\user\UserInterface $user
   *   The owner of the shortcut set.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    if ($account->hasPermission('administer shortcuts')) {
      // Administrators can switch anyone's shortcut set.
      return static::ALLOW;
    }

    if (!$account->hasPermission('access shortcuts')) {
      // The user has no permission to use shortcuts.
      return static::DENY;
    }

    if (!$account->hasPermission('switch shortcut sets')) {
      // The user has no permission to switch anyone's shortcut set.
      return static::DENY;
    }

    if ($user->id() == $account->id()) {
      // Users with the 'switch shortcut sets' permission can switch their own
      // shortcuts sets.
      return static::ALLOW;
    }
    return static::DENY;
  }

}
