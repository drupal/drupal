<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigAccessControlHandler.
 */

namespace Drupal\field;

use Drupal\Core\Access\AccessResult;
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
    if ($operation == 'delete') {
      $field_storage_entity = $entity->getFieldStorageDefinition();
      if ($field_storage_entity->isLocked()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($field_storage_entity);
      }
      else {
        return AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->entity_type . ' fields')->cacheUntilEntityChanges($field_storage_entity);
      }
    }
    return AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->entity_type . ' fields');
  }

}
