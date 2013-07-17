<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayModeAccessController.
 */

namespace Drupal\entity;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the access controller for entity display modes.
 */
class EntityDisplayModeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    elseif (in_array($operation, array('create', 'update', 'delete'))) {
      return user_access('administer display modes', $account);
    }
  }

}
