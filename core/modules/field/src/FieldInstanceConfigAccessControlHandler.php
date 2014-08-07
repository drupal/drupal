<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigAccessControlHandler.
 */

namespace Drupal\field;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the field instance entity type.
 *
 * @see \Drupal\field\Entity\FieldInstanceConfig
 */
class FieldInstanceConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'delete' && $entity->getFieldStorageDefinition()->isLocked()) {
      return FALSE;
    }
    return $account->hasPermission('administer ' . $entity->entity_type . ' fields');
  }

}
