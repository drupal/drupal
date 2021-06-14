<?php

namespace Drupal\taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the taxonomy vocabulary entity type.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
class VocabularyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'access taxonomy overview':
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['access taxonomy overview', 'administer taxonomy'], 'OR');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
