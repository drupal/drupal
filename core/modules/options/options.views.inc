<?php

/**
 * @file
 * Provide Views data for options.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for list fields. Have a different filter handler and
 * argument handlers for list fields. This should allow to select values of
 * the list.
 */
function options_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);

  $function = $field->getSetting('allowed_values_function');
  // If this field makes use of dynamic allowed options, we ignore the views
  // setting.
  if (!empty($function)) {
    return $data;
  }
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['id'] = 'list_field';
      }
      if (isset($field_data['argument']) && $field_name != 'delta') {
        if ($field->getType() == 'list_string') {
          $data[$table_name][$field_name]['argument']['id'] = 'string_list_field';
        }
        else {
          $data[$table_name][$field_name]['argument']['id'] = 'number_list_field';
        }
      }
    }
  }

  return $data;
}
