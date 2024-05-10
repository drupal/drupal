<?php

namespace Drupal\Core\Session;

/**
 * Represents a single entry for the calculated permissions.
 *
 * @see \Drupal\Core\Session\ChainPermissionCalculator
 */
class CalculatedPermissionsItem implements CalculatedPermissionsItemInterface {

  /**
   * Constructs a new CalculatedPermissionsItem.
   *
   * @param string[] $permissions
   *   The permission names.
   * @param bool $isAdmin
   *   (optional) Whether the item grants admin privileges.
   * @param string $scope
   *   (optional) The scope name, defaults to 'drupal'.
   * @param string|int $identifier
   *   (optional) The identifier within the scope, defaults to 'drupal'.
   */
  public function __construct(
    protected array $permissions,
    protected bool $isAdmin = FALSE,
    protected string $scope = AccessPolicyInterface::SCOPE_DRUPAL,
    protected string|int $identifier = AccessPolicyInterface::SCOPE_DRUPAL,
  ) {
    $this->permissions = $isAdmin ? [] : array_unique($permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function getScope(): string {
    return $this->scope;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier(): string|int {
    return $this->identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions(): array {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin(): bool {
    return $this->isAdmin;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission): bool {
    return $this->isAdmin() || in_array($permission, $this->permissions, TRUE);
  }

}
