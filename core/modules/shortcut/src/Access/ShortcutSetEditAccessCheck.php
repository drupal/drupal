<?php

/**
 * @file
 * Contains Drupal\shortcut\Access\ShortcutSetEditAccessCheck.
 */

namespace Drupal\shortcut\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for shortcut link delete routes.
 */
class ShortcutSetEditAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $account = \Drupal::currentUser();
    $shortcut_set = $request->attributes->get('shortcut_set');
    // Sufficiently-privileged users can edit their currently displayed shortcut
    // set, but not other sets. Shortcut administrators can edit any set.
    if ($account->hasPermission('administer shortcuts')) {
      return static::ALLOW;
    }
    if ($account->hasPermission('customize shortcut links')) {
      return !isset($shortcut_set) || $shortcut_set == shortcut_current_displayed_set() ? static::ALLOW : static::DENY;
    }
    return static::DENY;
  }

}
