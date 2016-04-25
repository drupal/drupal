<?php

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a user role entity.
 *
 * @ingroup user_api
 */
interface RoleInterface extends ConfigEntityInterface {

  /**
   * Role ID for anonymous users; should match what's in the "role" table.
   */
  const ANONYMOUS_ID = AccountInterface::ANONYMOUS_ROLE;

  /**
   * Role ID for authenticated users; should match what's in the "role" table.
   */
  const AUTHENTICATED_ID = AccountInterface::AUTHENTICATED_ROLE;

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
   * @return $this
   */
  public function grantPermission($permission);

  /**
   * Revokes a permissions from the user role.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return $this
   */
  public function revokePermission($permission);

  /**
   * Indicates that a role has all available permissions.
   *
   * @return bool
   *   TRUE if the role has all permissions.
   */
  public function isAdmin();

  /**
   * Sets the role to be an admin role.
   *
   * @param bool $is_admin
   *   TRUE if the role should be an admin role.
   *
   * @return $this
   */
  public function setIsAdmin($is_admin);

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight of this role.
   */
  public function getWeight();

  /**
   * Sets the weight to the given value.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
