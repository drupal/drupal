<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\AccessibleInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for checking access.
 */
interface AccessibleInterface {

  /**
   * Checks data value access.
   *
   * @param \Drupal\user\User $account
   *   (optional) The user account to check access for. Defaults to the current
   *   user.
   *
   * @return bool
   *   TRUE if the given user has access; otherwise FALSE.
   */
  public function access(\Drupal\user\User $account = NULL);
}
