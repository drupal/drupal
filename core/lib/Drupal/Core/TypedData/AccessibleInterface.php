<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\AccessibleInterface.
 */

namespace Drupal\Core\TypedData;

use Drupal\user\UserInterface;

/**
 * Interface for checking access.
 */
interface AccessibleInterface {

  /**
   * Checks data value access.
   *
   * @param string $operation
   *   (optional) The operation to be performed. Supported values are:
   *   - view
   *   - create
   *   - update
   *   - delete
   *   Defaults to 'view'.
   * @param \Drupal\user\UserInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the given user has access for the given operation, FALSE
   *   otherwise.
   *
   * @todo Don't depend on module level code.
   */
  public function access($operation = 'view', UserInterface $account = NULL);

}
