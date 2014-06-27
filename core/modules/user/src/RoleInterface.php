<?php

/**
 * @file
 * Contains \Drupal\user\Entity\RoleInterface.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a user role entity.
 *
 * @ingroup user_api
 */
interface RoleInterface extends ConfigEntityInterface {

  /**
   * Returns a list of permissions assigned to the role.
   *
   * @return array
   *   The permissions assigned to the role.
   */
  public function getPermissions();

  /**
   * Checks if the role has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   TRUE if the role has the permission, FALSE if not.
   */
  public function hasPermission($permission);

  /**
   * Grant permissions to the role.
   *
   * @param string $permission
   *   The permission to grant.
   *
   * @return RoleInterface
   *   The called object for chaining.
   */
  public function grantPermission($permission);

  /**
   * Revokes a permissions from the user role.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return RoleInterface
   *   The called object for chaining.
   */
  public function revokePermission($permission);

}
