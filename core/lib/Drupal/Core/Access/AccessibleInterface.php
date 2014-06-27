<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessibleInterface.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for checking access.
 *
 * @ingroup entity_api
 */
interface AccessibleInterface extends AccessInterface {

  /**
   * Checks data value access.
   *
   * @param string $operation
   *   The operation to be performed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool|null
   *   self::ALLOW, self::DENY, or self::KILL.
   */
  public function access($operation, AccountInterface $account = NULL);

}
