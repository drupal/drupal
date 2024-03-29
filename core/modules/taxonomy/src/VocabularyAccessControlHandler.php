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
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view label':
        return AccessResult::allowedIfHasPermissions($account, [
          'view vocabulary labels',
          'access taxonomy overview',
          'administer taxonomy',
        ], 'OR');

      case 'access taxonomy overview':
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['access taxonomy overview', 'administer taxonomy'], 'OR');

      case 'reset all weights':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer taxonomy',
          'edit terms in ' . $entity->id(),
        ], 'OR');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
