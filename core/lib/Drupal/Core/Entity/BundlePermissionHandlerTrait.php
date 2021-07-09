<?php

namespace Drupal\Core\Entity;

/**
 * Provides a method to simplify generating bundle level permissions.
 */
trait BundlePermissionHandlerTrait {

  /**
   * Builds a permissions array for the supplied bundles.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $bundles
   *   An array of bundles to generate permissions for.
   * @param callable $permission_builder
   *   A callable to generate the permissions for a particular bundle. Returns
   *   an array of permissions. See PermissionHandlerInterface::getPermissions()
   *   for the array structure.
   *
   * @return array
   *   Permissions array. See PermissionHandlerInterface::getPermissions() for
   *   the array structure.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  protected function generatePermissions(array $bundles, callable $permission_builder) {
    $permissions = [];
    foreach ($bundles as $bundle) {
      $permissions += array_map(
        function (array $perm) use ($bundle) {
          // This permission is generated on behalf of a bundle, therefore
          // add the bundle as a config dependency.
          $perm['dependencies'][$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
          return $perm;
        },
        $permission_builder($bundle)
      );
    }
    return $permissions;
  }

}
