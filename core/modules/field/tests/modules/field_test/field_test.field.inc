<?php

/**
 * @file
 * Defines a field type and its formatters and widgets.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldException;

/**
 * Implements hook_field_widget_info_alter().
 */
function field_test_field_widget_info_alter(&$info) {
  $info['test_field_widget_multiple']['field_types'][] = 'test_field';
}

/**
 * Implements hook_field_update_forbid().
 */
function field_test_field_update_forbid($field, $prior_field) {
  if ($field->getFieldType() == 'test_field' && $field->getFieldSetting('unchangeable') != $prior_field->getFieldSetting('unchangeable')) {
    throw new FieldException("field_test 'unchangeable' setting cannot be changed'");
  }
}

/**
 * Sample 'default value' callback.
 */
function field_test_default_value(EntityInterface $entity, $field, $instance) {
  return array(array('value' => 99));
}

/**
 * Implements hook_entity_field_access().
 */
function field_test_entity_field_access($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
  if ($field_definition->getFieldName() == "field_no_{$operation}_access") {
    return FALSE;
  }

  // Only grant view access to test_view_field fields when the user has
  // 'view test_view_field content' permission.
  if ($field_definition->getFieldName() == 'test_view_field' && $operation == 'view' && !$account->hasPermission('view test_view_field content')) {
    return FALSE;
  }

  return TRUE;
}
