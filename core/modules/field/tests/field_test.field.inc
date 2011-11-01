<?php

/**
 * @file
 * Defines a field type and its formatters and widgets.
 */

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
    ),
    'shape' => array(
      'label' => t('Shape'),
      'description' => t('Another dummy field type.'),
      'settings' => array(),
      'instance_settings' => array(),
      'default_widget' => 'test_field_widget',
      'default_formatter' => 'field_test_default',
    ),
    'hidden_test_field' => array(
      'no_ui' => TRUE,
      'label' => t('Hidden from UI test field'),
      'description' => t('Dummy hidden field type used for tests.'),
      'settings' => array(),
      'instance_settings' => array(),
      'default_widget' => 'test_field_widget',
      'default_formatter' => 'field_test_default',
    ),
  );
}

/**
 * Implements hook_field_update_forbid().
 */
function field_test_field_update_forbid($field, $prior_field, $has_data) {
  if ($field['type'] == 'test_field' && $field['settings']['unchangeable'] != $prior_field['settings']['unchangeable']) {
    throw new FieldException("field_test 'unchangeable' setting cannot be changed'");
  }
}

/**
 * Implements hook_field_load().
 */
function field_test_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  foreach ($items as $id => $item) {
    // To keep the test non-intrusive, only act for instances with the
    // test_hook_field_load setting explicitly set to TRUE.
    if ($instances[$id]['settings']['test_hook_field_load']) {
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
 * Implements hook_field_validate().
 *
 * Possible error codes:
 * - 'field_test_invalid': The value is invalid.
 */
function field_test_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
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
function field_test_field_is_empty($item, $field) {
  return empty($item['value']);
}

/**
 * Implements hook_field_settings_form().
 */
function field_test_field_settings_form($field, $instance, $has_data) {
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
 * Implements hook_field_widget_info().
 */
function field_test_field_widget_info() {
  return array(
    'test_field_widget' => array(
      'label' => t('Test field'),
      'field types' => array('test_field', 'hidden_test_field'),
      'settings' => array('test_widget_setting' => 'dummy test string'),
    ),
    'test_field_widget_multiple' => array(
      'label' => t('Test field 1'),
      'field types' => array('test_field'),
      'settings' => array('test_widget_setting_multiple' => 'dummy test string'),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_form().
 */
function field_test_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  switch ($instance['widget']['type']) {
    case 'test_field_widget':
      $element += array(
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]['value']) ? $items[$delta]['value'] : '',
      );
      return array('value' => $element);

    case 'test_field_widget_multiple':
      $values = array();
      foreach ($items as $delta => $value) {
        $values[] = $value['value'];
      }
      $element += array(
        '#type' => 'textfield',
        '#default_value' => implode(', ', $values),
        '#element_validate' => array('field_test_widget_multiple_validate'),
      );
      return $element;
  }
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
 * Implements hook_field_widget_error().
 */
function field_test_field_widget_error($element, $error, $form, &$form_state) {
  // @todo No easy way to differenciate widget types, we should receive it as a
  // parameter.
  if (isset($element['value'])) {
    // Widget is test_field_widget.
    $error_element = $element['value'];
  }
  else {
    // Widget is test_field_widget_multiple.
    $error_element = $element;
  }

  form_error($error_element, $error['message']);
}

/**
 * Implements hook_field_widget_settings_form().
 */
function field_test_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  $form['test_widget_setting'] = array(
    '#type' => 'textfield',
    '#title' => t('Field test field widget setting'),
    '#default_value' => $settings['test_widget_setting'],
    '#required' => FALSE,
    '#description' => t('A dummy form element to simulate field widget setting.'),
  );

  return $form;
}

/**
 * Implements hook_field_formatter_info().
 */
function field_test_field_formatter_info() {
  return array(
    'field_test_default' => array(
      'label' => t('Default'),
      'description' => t('Default formatter'),
      'field types' => array('test_field'),
      'settings' => array(
        'test_formatter_setting' => 'dummy test string',
      ),
    ),
    'field_test_multiple' => array(
      'label' => t('Multiple'),
      'description' => t('Multiple formatter'),
      'field types' => array('test_field'),
      'settings' => array(
        'test_formatter_setting_multiple' => 'dummy test string',
      ),
    ),
    'field_test_with_prepare_view' => array(
      'label' => t('Tests hook_field_formatter_prepare_view()'),
      'field types' => array('test_field'),
      'settings' => array(
        'test_formatter_setting_additional' => 'dummy test string',
      ),
    ),
  );
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function field_test_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $element = array();

  // The name of the setting depends on the formatter type.
  $map = array(
    'field_test_default' => 'test_formatter_setting',
    'field_test_multiple' => 'test_formatter_setting_multiple',
    'field_test_with_prepare_view' => 'test_formatter_setting_additional',
  );

  if (isset($map[$display['type']])) {
    $name = $map[$display['type']];

    $element[$name] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings[$name],
      '#required' => TRUE,
    );
  }

  return $element;
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function field_test_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $summary = '';

  // The name of the setting depends on the formatter type.
  $map = array(
    'field_test_default' => 'test_formatter_setting',
    'field_test_multiple' => 'test_formatter_setting_multiple',
    'field_test_with_prepare_view' => 'test_formatter_setting_additional',
  );

  if (isset($map[$display['type']])) {
    $name = $map[$display['type']];
    $summary = t('@setting: @value', array('@setting' => $name, '@value' => $settings[$name]));
  }

  return $summary;
}

/**
 * Implements hook_field_formatter_prepare_view().
 */
function field_test_field_formatter_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items, $displays) {
  foreach ($items as $id => $item) {
    // To keep the test non-intrusive, only act on the
    // 'field_test_with_prepare_view' formatter.
    if ($displays[$id]['type'] == 'field_test_with_prepare_view') {
      foreach ($item as $delta => $value) {
        // Don't add anything on empty values.
        if ($value) {
          $items[$id][$delta]['additional_formatter_value'] = $value['value'] + 1;
        }
      }
    }
  }
}

/**
 * Implements hook_field_formatter_view().
 */
function field_test_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();
  $settings = $display['settings'];

  switch ($display['type']) {
    case 'field_test_default':
      foreach ($items as $delta => $item) {
        $element[$delta] = array('#markup' => $settings['test_formatter_setting'] . '|' . $item['value']);
      }
      break;

    case 'field_test_with_prepare_view':
      foreach ($items as $delta => $item) {
        $element[$delta] = array('#markup' => $settings['test_formatter_setting_additional'] . '|' . $item['value'] . '|' . $item['additional_formatter_value']);
      }
      break;

    case 'field_test_multiple':
      $array = array();
      foreach ($items as $delta => $item) {
        $array[] = $delta . ':' . $item['value'];
      }
      $element[0] = array('#markup' => $settings['test_formatter_setting_multiple'] . '|' . implode('|', $array));
      break;
  }

  return $element;
}

/**
 * Sample 'default value' callback.
 */
function field_test_default_value($entity_type, $entity, $field, $instance) {
  return array(array('value' => 99));
}

/**
 * Implements hook_field_access().
 */
function field_test_field_access($op, $field, $entity_type, $entity, $account) {
  if ($field['field_name'] == "field_no_{$op}_access") {
    return FALSE;
  }
  return TRUE;
}
