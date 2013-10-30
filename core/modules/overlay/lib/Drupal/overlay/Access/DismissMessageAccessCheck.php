<?php

/**
 * @file
 * Contains \Drupal\overlay\Access\DismissMessageAccessCheck
 */

namespace Drupal\overlay\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access check for overlay user dismiss message routes.
 */
class DismissMessageAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_overlay_dismiss_message', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    if (!$account->hasPermission('access overlay')) {
      return static::DENY;
    }
    // It's unlikely, but possible that "access overlay" permission is granted
    // to the anonymous role. In this case, we do not display the message to
    // disable the overlay, so there is nothing to dismiss.
    if (!$account->id()) {
      return static::DENY;
    }
    return static::ALLOW;
  }

}
