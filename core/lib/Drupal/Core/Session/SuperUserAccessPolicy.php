<?php

declare(strict_types=1);

namespace Drupal\Core\Session;

/**
 * Grants user 1 an all access pass.
 */
final class SuperUserAccessPolicy extends AccessPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if (((int) $account->id()) !== 1) {
      return $calculated_permissions;
    }

    return $calculated_permissions->addItem(new CalculatedPermissionsItem([], TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return ['user.is_super_user'];
  }

}
