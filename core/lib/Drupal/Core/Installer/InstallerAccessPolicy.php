<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;

/**
 * Grants user 1 an all access pass during install.
 *
 * @internal
 *   The policy is only to be used by the installer.
 */
final class InstallerAccessPolicy extends AccessPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    // Prevent the access policy from working when not in the installer.
    if (((int) $account->id()) !== 1 || !InstallerKernel::installationAttempted()) {
      return $calculated_permissions;
    }

    return $calculated_permissions->addItem(new CalculatedPermissionsItem([], TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    // Note that cache contexts in the installer are ignored because
    // \Drupal\Core\Installer\NormalInstallerServiceProvider::register() changes
    // everything to use a memory cache. If this was not the case, then this
    // should also return a cache context related to the return value of
    // \Drupal\Core\Installer\InstallerKernel::installationAttempted().
    return ['user.is_super_user'];
  }

}
