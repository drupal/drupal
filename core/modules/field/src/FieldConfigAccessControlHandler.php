<?php

namespace Drupal\field;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the field config entity type.
 *
 * @see \Drupal\field\Entity\FieldConfig
 */
class FieldConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Delegate access control to the underlying field storage config entity:
    // the field config entity merely handles configuration for a particular
    // bundle of an entity type, the bulk of the logic and configuration is with
    // the field storage config entity. Therefore, if an operation is allowed on
    // a certain field storage config entity, it should also be allowed for all
    // associated field config entities.
    // @see \Drupal\Core\Field\FieldDefinitionInterface
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $field_storage_entity = $entity->getFieldStorageDefinition();
    return $field_storage_entity->access($operation, $account, TRUE);
  }

}
