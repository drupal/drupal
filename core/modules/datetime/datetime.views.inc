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
    $arguments = array(
      // Argument type => help text.
      'year' => t('Date in the form of YYYY.'),
      'month' => t('Date in the form of MM.'),
      'day' => t('Date in the form of DD.'),
    );
    foreach ($arguments as $argument_type => $help_text) {
      $data[$table_name][$field_storage->getName() . '_value_' . $argument_type] = array(
        'title' => $field_storage->getLabel() . ' (' . $argument_type . ')',
        'help' => $help_text,
        'argument' => array(
          'field' => $field_storage->getName() . '_value',
          'id' => 'datetime_' . $argument_type,
        ),
        'group' => $group,
      );
    }

    // Set the 'datetime' sort handler.
    $data[$table_name][$field_storage->getName() . '_value']['sort']['id'] = 'datetime';
  }

  return $data;
}
