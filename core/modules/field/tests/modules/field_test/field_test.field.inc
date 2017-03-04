<?php

/**
 * @file
 * Defines a field type and its formatters and widgets.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_widget_info_alter().
 */
function field_test_field_widget_info_alter(&$info) {
  $info['test_field_widget_multiple']['field_types'][] = 'test_field';
  $info['test_field_widget_multiple']['field_types'][] = 'test_field_with_preconfigured_options';
}

/**
 * Implements hook_field_storage_config_update_forbid().
 */
function field_test_field_storage_config_update_forbid(FieldStorageConfigInterface $field_storage, FieldStorageConfigInterface $prior_field_storage) {
  if ($field_storage->getType() == 'test_field' && $field_storage->getSetting('unchangeable') != $prior_field_storage->getSetting('unchangeable')) {
    throw new FieldStorageDefinitionUpdateForbiddenException("field_test 'unchangeable' setting cannot be changed'");
  }
}

/**
 * Sample 'default value' callback.
 */
function field_test_default_value(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
  return [['value' => 99]];
}

/**
 * Implements hook_entity_field_access().
 */
function field_test_entity_field_access($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
  if ($field_definition->getName() == "field_no_{$operation}_access") {
    return AccessResult::forbidden();
  }

  // Only grant view access to test_view_field fields when the user has
  // 'view test_view_field content' permission.
  if ($field_definition->getName() == 'test_view_field' && $operation == 'view') {
    return AccessResult::forbiddenIf(!$account->hasPermission('view test_view_field content'))->cachePerPermissions();
  }

  return AccessResult::allowed();
}
