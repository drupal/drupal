<?php

/**
 * @file
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Provides Views integration for any datetime-based fields.
 *
 * Overrides the default Views data for datetime-based fields, adding datetime
 * views plugins. Modules defining new datetime-based fields may use this
 * function to simplify Views integration.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage config entity.
 * @param array $data
 *   Field view data or views_field_default_views_data($field_storage) if empty.
 * @param string $column_name
 *   The schema column name with the datetime value.
 *
 * @return array
 *   The array of field views data with the datetime plugin.
 *
 * @see datetime_field_views_data()
 * @see datetime_range_field_views_data()
 */
function datetime_type_field_views_data_helper(FieldStorageConfigInterface $field_storage, array $data, $column_name) {
  // @todo This code only covers configurable fields, handle base table fields
  //   in https://www.drupal.org/node/2489476.
  $data = empty($data) ? views_field_default_views_data($field_storage) : $data;
  foreach ($data as $table_name => $table_data) {
    // Set the 'datetime' filter type.
    $data[$table_name][$field_storage->getName() . '_' . $column_name]['filter']['id'] = 'datetime';

    // Set the 'datetime' argument type.
    $data[$table_name][$field_storage->getName() . '_' . $column_name]['argument']['id'] = 'datetime';

    // Create year, month, and day arguments.
    $group = $data[$table_name][$field_storage->getName() . '_' . $column_name]['group'];
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
      $column_name_text = $column_name === $field_storage->getMainPropertyName() ? '' : ':' . $column_name;
      $data[$table_name][$field_storage->getName() . '_' . $column_name . '_' . $argument_type] = [
        'title' => t('@label@column (@argument)', [
          '@label' => $field_storage->getLabel(),
          '@column' => $column_name_text,
          '@argument' => $argument_type,
        ]),
        'help' => $help_text,
        'argument' => [
          'field' => $field_storage->getName() . '_' . $column_name,
          'id' => 'datetime_' . $argument_type,
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $field_storage->getName(),
        ],
        'group' => $group,
      ];
    }

    // Set the 'datetime' sort handler.
    $data[$table_name][$field_storage->getName() . '_' . $column_name]['sort']['id'] = 'datetime';
  }

  return $data;
}
