<?php

/**
 * @file
 * Contains \Drupal\system\DateFormatAccessController.
 */

namespace Drupal\system;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access controller for date formats.
 */
class DateFormatAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    // There are no restrictions on viewing a date format.
    if ($operation == 'view') {
      return TRUE;
    }
    // Locked date formats cannot be updated or deleted.
    elseif (in_array($operation, array('update', 'delete')) && $entity->isLocked()) {
      return FALSE;
    }

    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
