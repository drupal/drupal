<?php

namespace Drupal\system;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the date format entity type.
 *
 * @see \Drupal\system\Entity\DateFormat
 */
class DateFormatAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // There are no restrictions on viewing the label of a date format.
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    // Locked date formats cannot be updated or deleted.
    elseif (in_array($operation, ['update', 'delete'])) {
      if ($entity->isLocked()) {
        return AccessResult::forbidden('The DateFormat config entity is locked.')->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
