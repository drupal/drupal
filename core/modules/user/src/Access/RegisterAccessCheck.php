<?php

/**
 * @file
 * Contains \Drupal\user\Access\RegisterAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessInterface {

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
    $user_settings = \Drupal::config('user.settings');
    return AccessResult::allowedIf($account->isAnonymous() && $user_settings->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY)->cacheUntilConfigurationChanges($user_settings);
  }
}
