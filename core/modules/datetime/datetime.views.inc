<?php

/**
 * @file
 * Provides views data for the datetime module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function datetime_field_views_data(FieldStorageConfigInterface $field_storage) {
  // @todo This code only covers configurable fields, handle base table fields
  //   in https://www.drupal.org/node/2489476.
  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    // Set the 'datetime' filter type.
    $data[$table_name][$field_storage->getName() . '_value']['filter']['id'] = 'datetime';

    // Set the 'datetime' argument type.
    $data[$table_name][$field_storage->getName() . '_value']['argument']['id'] = 'datetime';

    // Create year, month, and day arguments.
    $group = $data[$table_name][$field_storage->getName() . '_value']['group'];
    $arguments = [
      // Argument type => help text.
      'year' => t('Date in the form of YYYY.'),
      'month' => t('Date in the form of MM (01 - 12).'),
      'day' => t('Date in the form of DD (01 - 31).'),
      'week' => t('Date in the form of WW (01 - 53).'),
      'year_month' => t('Date in the form of YYYYMM.'),
      'full_date' => t('Date in the form of CCYYMMDD.'),
    ];
    foreach ($arguments as $argument_type => $help_text) {
      $data[$table_name][$field_storage->getName() . '_value_' . $argument_type] = [
        'title' => $field_storage->getLabel() . ' (' . $argument_type . ')',
        'help' => $help_text,
        'argument' => [
          'field' => $field_storage->getName() . '_value',
          'id' => 'datetime_' . $argument_type,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $field_storage->getName(),
        ],
        'group' => $group,
      ];
    }

    // Set the 'datetime' sort handler.
    $data[$table_name][$field_storage->getName() . '_value']['sort']['id'] = 'datetime';
  }

  return $data;
}
