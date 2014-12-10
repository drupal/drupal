<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorage.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Controller class for user roles.
 */
class RoleStorage extends ConfigEntityStorage implements RoleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function isPermissionInRoles($permission, array $rids) {
    $has_permission = FALSE;
    foreach ($this->loadMultiple($rids) as $role) {
      if ($role->hasPermission($permission)) {
        $has_permission = TRUE;
        break;
      }
    }

    return $has_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRoleReferences(array $rids) {
    // Remove the role from all users.
    db_delete('user__roles')
      ->condition('target_id', $rids)
      ->execute();
  }

}
