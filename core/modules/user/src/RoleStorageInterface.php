<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorageInterface.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines a common interface for roel entity controller classes.
 */
interface RoleStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Returns whether a permission is in one of the passed in roles.
   *
   * @param string $permission
   *   The permission.
   * @param array $rids
   *   The list of role IDs to check.
   *
   * @return bool
   *   TRUE is the permission is in at least one of the roles. FALSE otherwise.
   */
  public function isPermissionInRoles($permission, array $rids);

  /**
   * Delete role references.
   *
   * @param array $rids
   *   The list of role IDs being deleted. The storage should
   *   remove permission and user references to this role.
   */
  public function deleteRoleReferences(array $rids);
}
