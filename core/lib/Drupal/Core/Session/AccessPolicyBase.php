<?php

namespace Drupal\Core\Session;

/**
 * Base class for access policies.
 */
abstract class AccessPolicyBase implements AccessPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(string $scope): bool {
    return $scope === AccessPolicyInterface::SCOPE_DRUPAL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    return (new RefinableCalculatedPermissions())->addCacheContexts($this->getPersistentCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions): void {}

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return [];
  }

}
