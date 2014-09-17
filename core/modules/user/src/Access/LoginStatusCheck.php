<?php

/**
 * @file
 * Contains \Drupal\user\Access\LoginStatusCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated())->cachePerRole();
  }

}
