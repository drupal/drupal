<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorageController.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for user roles.
 */
class RoleStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityInterface $entity) {
    if (!isset($entity->weight) && $roles = entity_load_multiple('user_role')) {
      // Set a role weight to make this new role last.
      $max = array_reduce($roles, function($max, $entity) {
        return $max > $entity->weight ? $max : $entity->weight;
      });
      $entity->weight = $max + 1;
    }
    parent::preSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);

    // Clear the user access cache.
    drupal_static_reset('user_access');
    drupal_static_reset('user_role_permissions');
  }

  /**
   * {@inheritdoc}
   */
  protected function postDelete($entities) {
    $rids = array_keys($entities);

    // Delete permission assignments.
    db_delete('role_permission')
      ->condition('rid', $rids)
      ->execute();
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
