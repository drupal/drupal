<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutSetAccessController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the shortcut entity type.
 */
class ShortcutSetAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'create':
      case 'update':
        if ($account->hasPermission('administer shortcuts')) {
          return TRUE;
        }
        if ($account->hasPermission('customize shortcut links')) {
          return !isset($entity) || $entity == shortcut_current_displayed_set($account);
        }
        return FALSE;
        break;

      case 'delete':
        if (!$account->hasPermission('administer shortcuts')) {
          return FALSE;
        }
        return $entity->id() != 'default';
        break;
    }
  }

}
