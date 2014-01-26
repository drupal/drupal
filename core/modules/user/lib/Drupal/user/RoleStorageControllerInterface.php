<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorageControllerInterface.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigStorageControllerInterface;

/**
 * Defines a common interface for roel entity controller classes.
 */
interface RoleStorageControllerInterface extends ConfigStorageControllerInterface {

  /**
   * Delete role references.
   *
   * @param array $rids
   *   The list of role IDs being deleted. The storage controller should
   *   remove permission and user references to this role.
   */
  public function deleteRoleReferences(array $rids);
}
