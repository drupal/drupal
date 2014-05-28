<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the taxonomy term entity.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class TermAccessController extends EntityAccessController {

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
