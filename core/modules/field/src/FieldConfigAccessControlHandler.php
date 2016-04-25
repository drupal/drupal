<?php

namespace Drupal\field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the field entity type.
 *
 * @see \Drupal\field\Entity\FieldConfig
 */
class FieldConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      $field_storage_entity = $entity->getFieldStorageDefinition();
      if ($field_storage_entity->isLocked()) {
        return AccessResult::forbidden()->addCacheableDependency($field_storage_entity);
      }
      else {
        return AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->getTargetEntityTypeId() . ' fields')->addCacheableDependency($field_storage_entity);
      }
    }
    return AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->getTargetEntityTypeId() . ' fields');
  }

}
