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

  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    // Sort the queried roles by their weight.
    uasort($queried_entities, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');

    parent::attachLoad($queried_entities, $revision_id);
  }

}
