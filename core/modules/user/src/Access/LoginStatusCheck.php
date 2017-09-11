<?php

namespace Drupal\user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route) {
    $required_status = filter_var($route->getRequirement('_user_is_logged_in'), FILTER_VALIDATE_BOOLEAN);
    $actual_status = $account->isAuthenticated();
    $access_result = AccessResult::allowedIf($required_status === $actual_status)->addCacheContexts(['user.roles:authenticated']);
    if (!$access_result->isAllowed()) {
      $access_result->setReason($required_status === TRUE ? 'This route can only be accessed by authenticated users.' : 'This route can only be accessed by anonymous users.');
    }
    return $access_result;
  }

}
