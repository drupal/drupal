<?php

namespace Drupal\Core\Session;

/**
 * Defines the calculated permissions item interface.
 */
interface CalculatedPermissionsItemInterface {

  /**
   * Returns the scope of the calculated permissions item.
   *
   * @return string
   *   The scope name.
   */
  public function getScope(): string;

  /**
   * Returns the identifier within the scope.
   *
   * @return string|int
   *   The identifier.
   */
  public function getIdentifier(): string|int;

  /**
   * Returns the permissions for the calculated permissions item.
   *
   * @return string[]
   *   The permission names.
   */
  public function getPermissions(): array;

  /**
   * Returns whether this item grants admin privileges in its scope.
   *
   * @return bool
   *   Whether this item grants admin privileges.
   */
  public function isAdmin(): bool;

  /**
   * Returns whether this item has a given permission.
   *
   * @param string $permission
   *   The permission name.
   *
   * @return bool
   *   Whether this item has the permission.
   */
  public function hasPermission(string $permission): bool;

}
