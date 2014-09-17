<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutSetAccessControlHandler.
 */

namespace Drupal\shortcut;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the shortcut set entity type.
 *
 * @see \Drupal\shortcut\Entity\ShortcutSet
 */
class ShortcutSetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        if ($account->hasPermission('administer shortcuts')) {
          return AccessResult::allowed()->cachePerRole();
        }
        if (!$account->hasPermission('access shortcuts')) {
          return AccessResult::create()->cachePerRole();
        }
        return AccessResult::allowedIf($account->hasPermission('customize shortcut links') && $entity == shortcut_current_displayed_set($account))->cachePerRole()->cacheUntilEntityChanges($entity);

      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('administer shortcuts') && $entity->id() != 'default')->cachePerRole();

      default:
        // No opinion.
        return AccessResult::create();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $condition = $account->hasPermission('administer shortcuts') || ($account->hasPermission('access shortcuts') && $account->hasPermission('customize shortcut links'));
    return AccessResult::allowedIf($condition)->cachePerRole();
  }

}
