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
  public function deleteRoleReferences(array $rids) {
    // Remove the role from all users.
    db_delete('users_roles')
      ->condition('rid', $rids)
      ->execute();
  }

}
