<?php

/**
 * @file
 * Contains \Drupal\user\Access\RoleAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on roles.
 *
 * You can specify the '_role' key on route requirements. If you specify a
 * single role, users with that role with have access. If you specify multiple
 * ones you can conjunct them with AND by using a "+" and with OR by using ",".
 */
class RoleAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, AccountInterface $account) {
    // Requirements just allow strings, so this might be a comma separated list.
    $rid_string = $route->getRequirement('_role');

    $explode_and = array_filter(array_map('trim', explode('+', $rid_string)));
    if (count($explode_and) > 1) {
      $diff = array_diff($explode_and, $account->getRoles());
      if (empty($diff)) {
        return static::ALLOW;
      }
    }
    else {
      $explode_or = array_filter(array_map('trim', explode(',', $rid_string)));
      $intersection = array_intersect($explode_or, $account->getRoles());
      if (!empty($intersection)) {
        return static::ALLOW;
      }
    }

    // If there is no allowed role, return NULL to give other checks a chance.
    return static::DENY;
  }

}
