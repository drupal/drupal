<?php

/**
 * @file
 * Contains \Drupal\user\Access\RoleAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on roles.
 *
 * You can specify the '_role' key on route requirements. If you specify a
 * single role, users with that role with have access. If you specify multiple
 * ones you can conjunct them with AND by using a "+" and with OR by using ",".
 */
class RoleAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_role', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // Requirements just allow strings, so this might be a comma separated list.
    $rid_string = $route->getRequirement('_role');

    // @todo Replace the role check with a correctly injected and session-using
    //   alternative.
    $account = $GLOBALS['user'];

    $explode_and = array_filter(array_map('trim', explode('+', $rid_string)));
    if (count($explode_and) > 1) {
      $diff = array_diff($explode_and, $account->roles);
      if (empty($diff)) {
        return TRUE;
      }
    }
    else {
      $explode_or = array_filter(array_map('trim', explode(',', $rid_string)));
      $intersection = array_intersect($explode_or, $account->roles);
      if (!empty($intersection)) {
        return TRUE;
      }
    }

    // If there is no allowed role, return NULL to give other checks a chance.
    return NULL;
  }

}
