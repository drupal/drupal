<?php

/**
 * @file
 * Provide views data and handlers for user.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function profile_views_data() {
  $data['profile_values']['moved to'] = 'profile_value';
  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['profile_value']['table']['group']  = t('Profile');

  $data['profile_value']['table']['join'] = array(
    'node' => array(
      'left_table' => 'profile_value',
      'left_field' => 'uid',
      'field' => 'uid',
    ),
    'users' => array(
      'left_table' => 'profile_value',
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  $fields = profile_views_get_fields();
  foreach ($fields as $field) {
    $table_name = 'profile_value_' . str_replace('-', '_', $field->name);
    $data[$table_name] = array(
      'table' => array(
        'group' => t('Profile'),
        'join' => array(
          'node' => array(
            'table' => 'profile_value',
            'left_table' => 'users',
            'left_field' => 'uid',
            'field' => 'uid',
            'extra' => array(array('field' => 'fid', 'value' => $field->fid)),
          ),
          'users' => array(
            'table' => 'profile_value',
            'left_field' => 'uid',
            'field' => 'uid',
            'extra' => array(array('field' => 'fid', 'value' => $field->fid)),
          ),
        ),
      ),
    );
    // All fields in the table are named 'value'.
    $data[$table_name]['value'] = profile_views_fetch_field($field);
  }

  return $data;
}

/**
 * Get all profile fields
 */
function profile_views_get_fields() {
  static $fields = NULL;

  if (!isset($fields)) {
    $fields = array();
    $results = db_query("SELECT * FROM {profile_field} ORDER BY category, weight");

    foreach ($results as $row) {
      if (!empty($row->options)) {
        if (!in_array(substr($row->options, 0, 2), array('a:', 'b:', 'i:', 'f:', 'o:', 's:', ))) {
          // unserialized fields default version
          $options = $row->options;
          unset($row->options);
          $row->options = $options;
        }
        else {
          // serialized fields or modified version
          $row->options = unserialize($row->options);
        }
      }
      $fields[$row->fid] = $row;
    }
  }
  return $fields;
}


/**
 * Add profile fields to view table
 */
function profile_views_fetch_field($field) {
  $data = array(
    'title' => t('@category: @field-name', array('@category' => $field->category, '@field-name' => $field->title)),
  );

  // Add fields specific to the profile type.
  switch ($field->type) {
    case 'textfield':
      $data += array(
        'help' => t('Profile textfield'),
        'field' => array(
          'handler' => 'views_handler_field_user',
          'click sortable' => TRUE,
        ),
        'sort' => array(
          'handler' => 'views_handler_sort',
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_string',
        ),
        'argument' => array(
          'handler' => 'views_handler_argument_string',
        ),
      );

      break;
    case 'textarea':
      $data += array(
        'help' => t('Profile textarea'),
        'field' => array(
          'handler' => 'views_handler_field_markup',
          'format' => filter_default_format(),
        ),
        'sort' => array(
          'handler' => 'views_handler_sort',
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_string',
        ),
      );

      break;
    case 'checkbox':
      $data += array(
        'help' => t('Profile checkbox'),
        'field' => array(
          'handler' => 'views_handler_field_boolean',
          'click sortable' => TRUE,
        ),
        'sort' => array(
          'handler' => 'views_handler_sort',
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_boolean_operator',
          'accept null' => TRUE,
        ),
        // @todo there ought to be a boolean argument handler
      );

      break;
    case 'url':
      $data += array(
        'help' => t('Profile URL'),
        'field' => array(
          'handler' => 'views_handler_field_url',
          'click sortable' => TRUE,
        ),
        'sort' => array(
          'handler' => 'views_handler_sort',
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_string',
        ),
      );

      break;
    case 'selection':
      $data += array(
        'help' => t('Profile selection'),
        'field' => array(
          'handler' => 'views_handler_field',
          'click sortable' => TRUE,
        ),
        'sort' => array(
          'handler' => 'views_handler_sort',
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_profile_selection',
          'fid' => $field->fid,
        ),
        'argument' => array(
          'handler' => 'views_handler_argument_string',
        ),
      );

      break;
    case 'list':
      $data += array(
        'help' => t('Profile freeform list %field-name.', array('%field-name' => $field->title)),
        'field' => array(
          'handler' => 'views_handler_field_profile_list',
          'no group by' => TRUE,
        ),
        'filter' => array(
          'handler' => 'views_handler_filter_string',
        ),
      );

      break;
    case 'date':
      $data += array(
        'help' => t('Profile date %field-name.', array('%field-name' => $field->title)),
        'field' => array(
          'handler' => 'views_handler_field_profile_date',
        ),
      );

      break;
  }

  // @todo: add access control to hidden fields.
  return $data;
}
