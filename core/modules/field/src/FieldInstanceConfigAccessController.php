<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigAccessController.
 */

namespace Drupal\field;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the field instance entity type.
 */
class FieldInstanceConfigAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'delete' && $entity->getField()->isLocked()) {
      return FALSE;
    }
    return $account->hasPermission('administer ' . $entity->entity_type . ' fields');
  }

}
