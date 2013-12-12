<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorageController.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Controller class for user roles.
 */
class RoleStorageController extends ConfigStorageController implements RoleStorageControllerInterface {

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
