<?php

namespace Drupal\tour;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the tour entity type.
 *
 * @see \Drupal\tour\Entity\Tour
 */
class TourAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermissions($account, ['access tour', 'administer site configuration'], 'OR');
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
