<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\ShortcutSetSwitchAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for shortcut link delete routes.
 */
class ShortcutSetSwitchAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    if ($account->hasPermission('administer shortcuts')) {
      // Administrators can switch anyone's shortcut set.
      return static::ALLOW;
    }

    if (!$account->hasPermission('switch shortcut sets')) {
      // The user has no permission to switch anyone's shortcut set.
      return static::DENY;
    }

    $user = $request->attributes->get('account');
    if (!isset($user) || $user->id() == $account->id()) {
      // Users with the 'switch shortcut sets' permission can switch their own
      // shortcuts sets.
      return static::ALLOW;
    }
    return static::DENY;
  }

}
