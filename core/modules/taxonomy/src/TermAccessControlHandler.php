<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessControlHandler.
 */

namespace Drupal\taxonomy;

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
        return $account->hasPermission('access content');
        break;

      case 'update':
        return $account->hasPermission("edit terms in {$entity->bundle()}") || $account->hasPermission('administer taxonomy');
        break;

      case 'delete':
        return $account->hasPermission("delete terms in {$entity->bundle()}") || $account->hasPermission('administer taxonomy');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer taxonomy');
  }

}
