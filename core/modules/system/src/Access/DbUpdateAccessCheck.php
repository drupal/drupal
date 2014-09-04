<?php

/**
 * @file
 * Contains Drupal\system\Access\DbUpdateAccessCheck.
 */

namespace Drupal\system\Access;

use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;

/**
 * Access check for database update routes.
 */
class DbUpdateAccessCheck implements AccessInterface {

  /**
   * Checks access for update routes.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    // Allow the global variable in settings.php to override the access check.
    if (Settings::get('update_free_access')) {
      return static::ALLOW;
    }

    return $account->hasPermission('administer software updates') ? static::ALLOW : static::KILL;
  }
}
