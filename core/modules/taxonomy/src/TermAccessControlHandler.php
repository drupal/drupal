<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessControlHandler.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the taxonomy term entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class TermAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');
        break;

      case 'update':
        return AccessResult::allowedIf($account->hasPermission("edit terms in {$entity->bundle()}") || $account->hasPermission('administer taxonomy'))->cachePerRole();
        break;

      case 'delete':
        return AccessResult::allowedIf($account->hasPermission("delete terms in {$entity->bundle()}") || $account->hasPermission('administer taxonomy'))->cachePerRole();
        break;

      default:
        // No opinion.
        return AccessResult::create();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer taxonomy');
  }

}
