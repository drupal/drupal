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
   * Role ID for anonymous users; should match the 'role' entity ID.
   */
  const ANONYMOUS_ID = AccountInterface::ANONYMOUS_ROLE;

  /**
   * Role ID for authenticated users; should match the 'role' entity ID.
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
   * Grant a permission to the role.
   *
   * @param string $permission
   *   The permission to grant.
   *
   * @return $this
   */
  public function grantPermission($permission);

  /**
   * Grants permissions to the role.
   *
   * @param array $permissions
   *   The permissions to grant.
   *
   * @return $this
   */
  public function grantPermissions(array $permissions): static;

  /**
   * Changes permissions for a user role.
   *
   * This method may be used to grant and revoke multiple permissions at once.
   * For example, when a form exposes checkboxes to configure permissions for a
   * role, the form submit handler may directly pass the submitted values for
   * the checkboxes form element to this function.
   *
   * @param array $permissions
   *   (optional) An associative array, where the key holds the permission name
   *   and the value determines whether to grant or revoke that permission. Any
   *   value that evaluates to TRUE will cause the permission to be granted.
   *   Any value that evaluates to FALSE will cause the permission to be
   *   revoked.
   *   @code
   *     [
   *       'administer nodes' => 0,                // Revoke 'administer nodes'
   *       'administer blocks' => FALSE,           // Revoke 'administer blocks'
   *       'access user profiles' => 1,            // Grant 'access user profiles'
   *       'access content' => TRUE,               // Grant 'access content'
   *       'access comments' => 'access comments', // Grant 'access comments'
   *     ]
   *   @endcode
   *   Existing permissions are not changed, unless specified in $permissions.
   *
   * @return $this
   *
   * @see RoleInterface::grantPermissions()
   * @see RoleInterface::revokePermissions()
   */
  public function changePermissions(array $permissions): static;

  /**
   * Revokes a permission from the user role.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return $this
   */
  public function revokePermission($permission);

  /**
   * Revokes permissions from the user role.
   *
   * @param array $permissions
   *   The permissions to revoke.
   *
   * @return $this
   */
  public function revokePermissions(array $permissions): static;

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
