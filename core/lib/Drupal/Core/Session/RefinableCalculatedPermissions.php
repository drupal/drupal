<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Represents a calculated set of permissions with cacheable metadata.
 *
 * @see \Drupal\Core\Session\AccessPolicyProcessor
 */
class RefinableCalculatedPermissions implements RefinableCalculatedPermissionsInterface {

  use CalculatedPermissionsTrait;
  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function addItem(CalculatedPermissionsItemInterface $item, bool $overwrite = FALSE): self {
    if (!$overwrite && $existing = $this->getItem($item->getScope(), $item->getIdentifier())) {
      $item = static::mergeItems($existing, $item);
    }
    $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(string $scope = AccessPolicyInterface::SCOPE_DRUPAL, string|int $identifier = AccessPolicyInterface::SCOPE_DRUPAL): self {
    unset($this->items[$scope][$identifier]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItems(): self {
    $this->items = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItemsByScope(string $scope): self {
    unset($this->items[$scope]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(CalculatedPermissionsInterface $calculated_permissions): self {
    foreach ($calculated_permissions->getItems() as $item) {
      $this->addItem($item);
    }
    $this->addCacheableDependency($calculated_permissions);
    return $this;
  }

  /**
   * Merges two items of identical scope and identifier.
   *
   * @param \Drupal\Core\Session\CalculatedPermissionsItemInterface $a
   *   The first item to merge.
   * @param \Drupal\Core\Session\CalculatedPermissionsItemInterface $b
   *   The second item to merge.
   *
   * @return \Drupal\Core\Session\CalculatedPermissionsItemInterface
   *   A new item representing the merger of both items.
   */
  protected static function mergeItems(CalculatedPermissionsItemInterface $a, CalculatedPermissionsItemInterface $b): CalculatedPermissionsItemInterface {
    // If either of the items is admin, the new one is too.
    $is_admin = $a->isAdmin() || $b->isAdmin();

    // Admin items don't need to have any permissions.
    $permissions = [];
    if (!$is_admin) {
      $permissions = array_unique(array_merge($a->getPermissions(), $b->getPermissions()));
    }

    return new CalculatedPermissionsItem($permissions, $is_admin, $a->getScope(), $a->getIdentifier());
  }

}
