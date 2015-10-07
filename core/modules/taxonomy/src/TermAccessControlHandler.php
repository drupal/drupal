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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');
        break;

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ["edit terms in {$entity->bundle()}", 'administer taxonomy'], 'OR');
        break;

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ["delete terms in {$entity->bundle()}", 'administer taxonomy'], 'OR');
        break;

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer taxonomy');
  }

}
