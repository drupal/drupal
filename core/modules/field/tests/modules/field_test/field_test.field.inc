<?php

/**
 * @file
 * Defines a field type and its formatters and widgets.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldException;
use Drupal\field\FieldInterface;

/**
 * Implements hook_field_info().
 */
function field_test_field_info() {
  return array(
    'test_field' => array(
      'label' => t('Test field'),
      'description' => t('Dummy field type used for tests.'),
      'settings' => array(
        'test_field_setting' => 'dummy test string',
        'changeable' => 'a changeable field setting',
        'unchangeable' => 'an unchangeable field setting',
      ),
      'instance_settings' => array(
        'test_instance_setting' => 'dummy test string',
        'test_hook_field_load' => FALSE,
      ),
      'default_widget' => 'test_field_widget',
      'default_formatter' => 'field_test_default',
      'class' => 'Drupal\field_test\Type\TestItem',
    ),
    'shape' => array(
      'label' => t('Shape'),
      'description' => t('Another dummy field type.'),
      'settings' => array(
        'foreign_key_name' => 'shape',
      ),
      'instance_settings' => array(),
      'default_widget' => 'test_field_widget',
      'default_formatter' => 'field_test_default',
      'class' => 'Drupal\field_test\Type\ShapeItem',
    ),
    'hidden_test_field' => array(
      'no_ui' => TRUE,
      'label' => t('Hidden from UI test field'),
      'description' => t('Dummy hidden field type used for tests.'),
      'settings' => array(),
      'instance_settings' => array(),
      'default_widget' => 'test_field_widget',
      'default_formatter' => 'field_test_default',
      'class' => 'Drupal\field_test\Type\HiddenTestItem',
    ),
  );
}

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
  if ($field['type'] == 'test_field' && $field['settings']['unchangeable'] != $prior_field['settings']['unchangeable']) {
    throw new FieldException("field_test 'unchangeable' setting cannot be changed'");
  }
}

/**
 * Implements hook_field_load().
 */
function field_test_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  $args = func_get_args();
  field_test_memorize(__FUNCTION__, $args);

  foreach ($items as $id => $item) {
    // To keep the test non-intrusive, only act for instances with the
    // test_hook_field_load setting explicitly set to TRUE.
    if (!empty($instances[$id]['settings']['test_hook_field_load'])) {
      foreach ($item as $delta => $value) {
        // Don't add anything on empty values.
        if ($value) {
          $items[$id][$delta]['additional_key'] = 'additional_value';
        }
      }
    }
  }
}

/**
 * Implements hook_field_insert().
 */
function field_test_field_insert(EntityInterface $entity, $field, $instance, $items) {
  $args = func_get_args();
  field_test_memorize(__FUNCTION__, $args);
}

/**
 * Implements hook_field_update().
 */
function field_test_field_update(EntityInterface $entity, $field, $instance, $items) {
  $args = func_get_args();
  field_test_memorize(__FUNCTION__, $args);
}

/**
 * Implements hook_field_delete().
 */
function field_test_field_delete(EntityInterface $entity, $field, $instance, $items) {
  $args = func_get_args();
  field_test_memorize(__FUNCTION__, $args);
}

/**
 * Implements hook_field_validate().
 *
 * Possible error codes:
 * - 'field_test_invalid': The value is invalid.
 */
function field_test_field_validate(EntityInterface $entity = NULL, $field, $instance, $langcode, $items, &$errors) {
  $args = func_get_args();
  field_test_memorize(__FUNCTION__, $args);

  foreach ($items as $delta => $item) {
    if ($item['value'] == -1) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'field_test_invalid',
        'message' => t('%name does not accept the value -1.', array('%name' => $instance['label'])),
      );
    }
  }
}

/**
 * Implements hook_field_is_empty().
 */
function field_test_field_is_empty($item, $field_type) {
  if ($field_type == 'test_field') {
    return empty($item['value']);
  }
  return empty($item['shape']) && empty($item['color']);
}

/**
 * Implements hook_field_settings_form().
 */
function field_test_field_settings_form($field, $instance) {
  $settings = $field['settings'];

  $form['test_field_setting'] = array(
    '#type' => 'textfield',
    '#title' => t('Field test field setting'),
    '#default_value' => $settings['test_field_setting'],
    '#required' => FALSE,
    '#description' => t('A dummy form element to simulate field setting.'),
  );

  return $form;
}

/**
 * Implements hook_field_instance_settings_form().
 */
function field_test_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings'];

  $form['test_instance_setting'] = array(
    '#type' => 'textfield',
    '#title' => t('Field test field instance setting'),
    '#default_value' => $settings['test_instance_setting'],
    '#required' => FALSE,
    '#description' => t('A dummy form element to simulate field instance setting.'),
  );

  return $form;
}

/**
 * Form element validation handler for 'test_field_widget_multiple' widget.
 */
function field_test_widget_multiple_validate($element, &$form_state) {
  $values = array_map('trim', explode(',', $element['#value']));
  $items = array();
  foreach ($values as $value) {
    $items[] = array('value' => $value);
  }
  form_set_value($element, $items, $form_state);
}

/**
 * Sample 'default value' callback.
 */
function field_test_default_value(EntityInterface $entity, $field, $instance) {
  return array(array('value' => 99));
}

/**
 * Implements hook_field_access().
 */
function field_test_field_access($op, FieldInterface $field, $entity_type, $entity, $account) {
  if ($field['field_name'] == "field_no_{$op}_access") {
    return FALSE;
  }

  // Only grant view access to test_view_field fields when the user has
  // 'view test_view_field content' permission.
  if ($field['field_name'] == 'test_view_field' && $op == 'view' && !$account->hasPermission('view test_view_field content')) {
    return FALSE;
  }

  return TRUE;
}
