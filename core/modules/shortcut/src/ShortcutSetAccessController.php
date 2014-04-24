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
      case 'update':
        if ($account->hasPermission('administer shortcuts')) {
          return TRUE;
        }
        if ($account->hasPermission('customize shortcut links')) {
          return $entity == shortcut_current_displayed_set($account);
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

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer shortcuts') || $account->hasPermission('customize shortcut links');
  }

}
