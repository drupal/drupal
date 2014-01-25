<?php

/**
 * @file
 * Contains \Drupal\system\MenuAccessController.
 */

namespace Drupal\system;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the menu entity type.
 */
class MenuAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    // Locked menus could not be deleted.
    elseif ($operation == 'delete' && $entity->isLocked()) {
      return FALSE;
    }

    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
