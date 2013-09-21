<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\ShortcutSetSwitchAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for shortcut link delete routes.
 */
class ShortcutSetSwitchAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_shortcut_set_switch');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $user = \Drupal::currentUser();
    $account = $request->attributes->get('account');

    if ($user->hasPermission('administer shortcuts')) {
      // Administrators can switch anyone's shortcut set.
      return static::ALLOW;
    }

    if (!$user->hasPermission('switch shortcut sets')) {
      // The user has no permission to switch anyone's shortcut set.
      return static::DENY;
    }

    if (!isset($account) || $user->id() == $account->id()) {
      // Users with the 'switch shortcut sets' permission can switch their own
      // shortcuts sets.
      return static::ALLOW;
    }
    return static::DENY;
  }

}
