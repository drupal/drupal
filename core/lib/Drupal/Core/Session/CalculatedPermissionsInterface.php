<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the calculated permissions interface.
 */
interface CalculatedPermissionsInterface extends CacheableDependencyInterface {

  /**
   * Retrieves a single calculated permission item from a given scope.
   *
   * @param string $scope
   *   (optional) The scope name to get the item for, defaults to 'drupal'.
   * @param string|int $identifier
   *   (optional) The identifier to get the item for, defaults to 'drupal'.
   *
   * @return \Drupal\Core\Session\CalculatedPermissionsItemInterface|false
   *   The calculated permission item or FALSE if it could not be found.
   */
  public function getItem(string $scope = AccessPolicyInterface::SCOPE_DRUPAL, string|int $identifier = AccessPolicyInterface::SCOPE_DRUPAL): CalculatedPermissionsItemInterface|false;

  /**
   * Retrieves all of the calculated permission items, regardless of scope.
   *
   * @return \Drupal\Core\Session\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items.
   */
  public function getItems(): array;

  /**
   * Retrieves all of the scopes that have items for them.
   *
   * @return string[]
   *   The scope names that are in use.
   */
  public function getScopes(): array;

  /**
   * Retrieves all of the calculated permission items for the given scope.
   *
   * @param string $scope
   *   (optional) The scope name to get the item for, defaults to 'drupal'.
   *
   * @return \Drupal\Core\Session\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items for the given scope.
   */
  public function getItemsByScope(string $scope = AccessPolicyInterface::SCOPE_DRUPAL): array;

}
