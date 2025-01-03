<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;

/**
 * Defines the refinable calculated permissions interface.
 */
interface RefinableCalculatedPermissionsInterface extends RefinableCacheableDependencyInterface, CalculatedPermissionsInterface {

  /**
   * Adds a calculated permission item.
   *
   * @param \Drupal\Core\Session\CalculatedPermissionsItemInterface $item
   *   The calculated permission item.
   * @param bool $overwrite
   *   (optional) Whether to overwrite an item if there already is one for the
   *   given identifier within the scope. Defaults to FALSE, meaning a merge
   *   will take place instead.
   *
   * @return $this
   */
  public function addItem(CalculatedPermissionsItemInterface $item, bool $overwrite = FALSE): self;

  /**
   * Removes a single calculated permission item from a given scope.
   *
   * @param string $scope
   *   (optional) The scope name to remove the item from, defaults to 'drupal'.
   * @param string|int $identifier
   *   (optional) The scope identifier to remove the item from, defaults to
   *   'drupal'.
   *
   * @return $this
   */
  public function removeItem(string $scope = AccessPolicyInterface::SCOPE_DRUPAL, string|int $identifier = AccessPolicyInterface::SCOPE_DRUPAL): self;

  /**
   * Removes all of the calculated permission items, regardless of scope.
   *
   * @return $this
   */
  public function removeItems(): self;

  /**
   * Removes all of the calculated permission items for the given scope.
   *
   * @param string $scope
   *   The scope name to remove the items for.
   *
   * @return $this
   */
  public function removeItemsByScope(string $scope): self;

  /**
   * Merge another calculated permissions object into this one.
   *
   * This merges (not replaces) all permissions and cacheable metadata.
   *
   * @param \Drupal\Core\Session\CalculatedPermissionsInterface $other
   *   The other calculated permissions object to merge into this one.
   *
   * @return $this
   */
  public function merge(CalculatedPermissionsInterface $other): self;

}
