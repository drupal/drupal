<?php

/**
 * @file
 * Provide Views data and handlers for field.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 *
 * Field modules can implement hook_field_views_data() to override
 * the default behavior for adding fields.
 */
function field_views_data() {
  $data = array();
  foreach (field_info_fields() as $field) {
    if ($field['storage']['type'] != 'field_sql_storage') {
      continue;
    }

    $module = $field['module'];
    $result = (array) module_invoke($module, 'field_views_data', $field);

    if (empty($result)) {
      $result = field_views_field_default_views_data($field);
    }
    drupal_alter('field_views_data', $result, $field, $module);

    if (is_array($result)) {
      $data = drupal_array_merge_deep($result, $data);
    }
  }

  return $data;
}

/**
 * Implements hook_views_data_alter().
 *
 * Field modules can implement hook_field_views_data_views_data_alter() to
 * alter the views data on a per field basis. This is weirdly named so as
 * not to conflict with the drupal_alter('field_views_data') in
 * field_views_data.
 */
function field_views_data_alter(&$data) {
  foreach (field_info_fields() as $field) {
    if ($field['storage']['type'] != 'field_sql_storage') {
      continue;
    }

    $function = $field['module'] . '_field_views_data_views_data_alter';
    if (function_exists($function)) {
      $function($data, $field);
    }
  }
}

/**
 * Returns the label of a certain field.
 *
 * Therefore it looks up in all bundles to find the most used instance.
 */
function field_views_field_label($field_name) {
  $label_counter = array();
  $all_labels = array();
  // Count the amount of instances per label per field.
  $instances = field_info_instances();
  foreach ($instances as $entity_name => $entity_type) {
    foreach ($entity_type as $bundle) {
      if (isset($bundle[$field_name])) {
        $label_counter[$bundle[$field_name]['label']] = isset($label_counter[$bundle[$field_name]['label']]) ? ++$label_counter[$bundle[$field_name]['label']] : 1;
        $all_labels[$entity_name][$bundle[$field_name]['label']] = TRUE;
      }
    }
  }
  if (empty($label_counter)) {
    return array($field_name, $all_labels);
  }
  // Sort the field lables by it most used label and return the most used one.
  arsort($label_counter);
  $label_counter = array_keys($label_counter);
  return array($label_counter[0], $all_labels);
}

/**
 * Default views data implementation for a field.
 */
function field_views_field_default_views_data($field) {
  $field_types = field_info_field_types();

  // Check the field module is available.
  if (!isset($field_types[$field['type']])) {
    return;
  }

  $data = array();

  $current_table = _field_sql_storage_tablename($field);
  $revision_table = _field_sql_storage_revision_tablename($field);

  // The list of entity:bundle that this field is used in.
  $bundles_names = array();
  $supports_revisions = FALSE;
  $entity_tables = array();
  $current_tables = array();
  $revision_tables = array();
  $groups = array();

  $group_name = count($field['bundles']) > 1 ? t('Field') : NULL;

  // Build the relationships between the field table and the entity tables.
  foreach ($field['bundles'] as $entity => $bundles) {
    $entity_info = entity_get_info($entity);
    $groups[$entity] = $entity_info['label'];

    // Override Node to Content.
    if ($groups[$entity] == t('Node')) {
      $groups[$entity] = t('Content');
    }

    // If only one bundle use this as the default name.
    if (empty($group_name)) {
      $group_name = $groups[$entity];
    }

    $entity_tables[$entity_info['base table']] = $entity;
    $current_tables[$entity] = $entity_info['base table'];
    if (isset($entity_info['revision table'])) {
      $entity_tables[$entity_info['revision table']] = $entity;
      $revision_tables[$entity] = $entity_info['revision table'];
    }

    $data[$current_table]['table']['join'][$entity_info['base table']] = array(
      'left_field' => $entity_info['entity keys']['id'],
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'entity_type', 'value' => $entity),
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
      ),
    );

    if (!empty($entity_info['entity keys']['revision']) && !empty($entity_info['revision table'])) {
      $data[$revision_table]['table']['join'][$entity_info['revision table']] = array(
        'left_field' => $entity_info['entity keys']['revision'],
        'field' => 'revision_id',
        'extra' => array(
          array('field' => 'entity_type', 'value' => $entity),
          array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        ),
      );

      $supports_revisions = TRUE;
    }

    foreach ($bundles as $bundle) {
      $bundles_names[] = t('@entity:@bundle', array('@entity' => $entity, '@bundle' => $bundle));
    }
  }

  $tables = array();
  $tables[FIELD_LOAD_CURRENT] = $current_table;
  if ($supports_revisions) {
    $tables[FIELD_LOAD_REVISION] = $revision_table;
  }

  $add_fields = array('delta', 'language', 'bundle');
  foreach ($field['columns'] as $column_name => $attributes) {
    $add_fields[] = _field_sql_storage_columnname($field['field_name'], $column_name);
  }

  // Note: we don't have a label available here, because we are at the field
  // level, not at the instance level. So we just go through all instances
  // and take the one which is used the most frequently.
  $field_name = $field['field_name'];
  list($label, $all_labels) = field_views_field_label($field_name);
  foreach ($tables as $type => $table) {
    if ($type == FIELD_LOAD_CURRENT) {
      $group = $group_name;
      $old_column = 'entity_id';
      $column = $field['field_name'];
    }
    else {
      $group = t('@group (historical data)', array('@group' => $group_name));
      $old_column = 'revision_id';
      $column = $field['field_name'] . '-' . $old_column;
    }

    $data[$table][$old_column]['field']['moved to'] = array($table, $column);
    $data[$table][$column] = array(
      'group' => $group,
      'title' => $label,
      'title short' => $label,
      'help' =>  t('Appears in: @bundles.', array('@bundles' => implode(', ', $bundles_names))),
    );

    // Go through and create a list of aliases for all possible combinations of
    // entity type + name.
    $aliases = array();
    $also_known = array();
    foreach ($all_labels as $entity_name => $labels) {
      foreach ($labels as $label_name => $true) {
        if ($type == FIELD_LOAD_CURRENT) {
          if ($group_name != $groups[$entity_name] || $label != $label_name) {
            $aliases[] = array(
              'base' => $current_tables[$entity_name],
              'group' => $groups[$entity_name],
              'title' => $label_name,
              'help' => t('This is an alias of @group: @field.', array('@group' => $group_name, '@field' => $label)),
            );
          }
          $also_known[] = t('@group: @field', array('@group' => $groups[$entity_name], '@field' => $label_name));
        }
        else {
          if ($group_name != $groups[$entity_name] && $label != $label_name && isset($revision_tables[$entity_name])) {
            $aliases[] = array(
              'base' => $revision_tables[$entity_name],
              'group' => t('@group (historical data)', array('@group' => $groups[$entity_name])),
              'title' => $label_name,
              'help' => t('This is an alias of @group: @field.', array('@group' => $group_name, '@field' => $label)),
            );
          }
          $also_known[] = t('@group (historical data): @field', array('@group' => $groups[$entity_name], '@field' => $label_name));
        }
      }
    }
    if ($aliases) {
      $data[$table][$column]['aliases'] = $aliases;
      $data[$table][$column]['help'] .= ' ' . t('Also known as: !also.', array('!also' => implode(', ', $also_known)));
    }

    $keys = array_keys($field['columns']);
    $real_field = reset($keys);
    $data[$table][$column]['field'] = array(
      'table' => $table,
      'handler' => 'views_handler_field_field',
      'click sortable' => TRUE,
      'field_name' => $field['field_name'],
      // Provide a real field for group by.
      'real field' => $column . '_' . $real_field,
      'additional fields' => $add_fields,
      'entity_tables' => $entity_tables,
      // Default the element type to div, let the UI change it if necessary.
      'element type' => 'div',
      'is revision' => $type == FIELD_LOAD_REVISION,
    );
  }

  foreach ($field['columns'] as $column => $attributes) {
    $allow_sort = TRUE;

    // Identify likely filters and arguments for each column based on field type.
    switch ($attributes['type']) {
      case 'int':
      case 'mediumint':
      case 'tinyint':
      case 'bigint':
      case 'serial':
      case 'numeric':
      case 'float':
        $filter = 'views_handler_filter_numeric';
        $argument = 'views_handler_argument_numeric';
        $sort = 'views_handler_sort';
        break;
      case 'text':
      case 'blob':
        // It does not make sense to sort by blob or text.
        $allow_sort = FALSE;
      default:
        $filter = 'views_handler_filter_string';
        $argument = 'views_handler_argument_string';
        $sort = 'views_handler_sort';
        break;
    }


    if (count($field['columns']) == 1 || $column == 'value') {
      $title = t('@label (!name)', array('@label' => $label, '!name' => $field['field_name']));
      // CCK used the first 10 characters of $label. Probably doesn't matter.
      $title_short = $label;
    }
    else {
      $title = t('@label (!name:!column)', array('@label' => $label, '!name' => $field['field_name'], '!column' => $column));
      $title_short = t('@label:!column', array('@label' => $label, '!column' => $column));
    }

    foreach ($tables as $type => $table) {
      if ($type == FIELD_LOAD_CURRENT) {
        $group = $group_name;
      }
      else {
        $group = t('@group (historical data)', array('@group' => $group_name));
      }
      $column_real_name = $field['storage']['details']['sql'][$type][$table][$column];

      // Load all the fields from the table by default.
      $additional_fields = array_values($field['storage']['details']['sql'][$type][$table]);

      $data[$table][$column_real_name] = array(
        'group' => $group,
        'title' => $title,
        'title short' => $title_short,
        'help' =>  t('Appears in: @bundles.', array('@bundles' => implode(', ', $bundles_names))),
      );

      // Go through and create a list of aliases for all possible combinations of
      // entity type + name.
      $aliases = array();
      $also_known = array();
      foreach ($all_labels as $entity_name => $labels) {
        foreach ($labels as $label_name => $true) {
          if ($group_name != $groups[$entity_name] || $label != $label_name) {
            if (count($field['columns']) == 1 || $column == 'value') {
              $alias_title = t('@label (!name)', array('@label' => $label_name, '!name' => $field['field_name']));
              // CCK used the first 10 characters of $label. Probably doesn't matter.
            }
            else {
              $alias_title = t('@label (!name:!column)', array('@label' => $label_name, '!name' => $field['field_name'], '!column' => $column));
            }
            $aliases[] = array(
              'group' => $groups[$entity_name],
              'title' => $alias_title,
              'help' => t('This is an alias of @group: @field.', array('@group' => $group_name, '@field' => $title)),
            );
          }
          $also_known[] = t('@group: @field', array('@group' => $groups[$entity_name], '@field' => $title));
        }
      }
      if ($aliases) {
        $data[$table][$column_real_name]['aliases'] = $aliases;
        $data[$table][$column_real_name]['help'] .= ' ' . t('Also known as: !also.', array('!also' => implode(', ', $also_known)));
      }

      $data[$table][$column_real_name]['argument'] = array(
        'field' => $column_real_name,
        'table' => $table,
        'handler' => $argument,
        'additional fields' => $additional_fields,
        'field_name' => $field['field_name'],
        'empty field name' => t('- No value -'),
      );
      $data[$table][$column_real_name]['filter'] = array(
        'field' => $column_real_name,
        'table' => $table,
        'handler' => $filter,
        'additional fields' => $additional_fields,
        'field_name' => $field['field_name'],
        'allow empty' => TRUE,
      );
      if (!empty($allow_sort)) {
        $data[$table][$column_real_name]['sort'] = array(
          'field' => $column_real_name,
          'table' => $table,
          'handler' => $sort,
          'additional fields' => $additional_fields,
          'field_name' => $field['field_name'],
        );
      }

      // Expose additional delta column for multiple value fields.
      if ($field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED) {
        $title_delta = t('@label (!name:delta)', array('@label' => $label, '!name' => $field['field_name']));
        $title_short_delta = t('@label:delta', array('@label' => $label));

        $data[$table]['delta'] = array(
          'group' => $group,
          'title' => $title_delta,
          'title short' => $title_short_delta,
          'help' => t('Delta - Appears in: @bundles.', array('@bundles' => implode(', ', $bundles_names))),
        );
        $data[$table]['delta']['field'] = array(
          'handler' => 'views_handler_field_numeric',
        );
        $data[$table]['delta']['argument'] = array(
          'field' => 'delta',
          'table' => $table,
          'handler' => 'views_handler_argument_numeric',
          'additional fields' => $additional_fields,
          'empty field name' => t('- No value -'),
          'field_name' => $field['field_name'],
        );
        $data[$table]['delta']['filter'] = array(
          'field' => 'delta',
          'table' => $table,
          'handler' => 'views_handler_filter_numeric',
          'additional fields' => $additional_fields,
          'field_name' => $field['field_name'],
          'allow empty' => TRUE,
        );
        $data[$table]['delta']['sort'] = array(
          'field' => 'delta',
          'table' => $table,
          'handler' => 'views_handler_sort',
          'additional fields' => $additional_fields,
          'field_name' => $field['field_name'],
        );
      }
    }
  }

  return $data;
}

/**
 * Have a different filter handler for lists. This should allow to select values of the list.
 */
function list_field_views_data($field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['handler'] = 'views_handler_filter_field_list';
      }
      if (isset($field_data['argument']) && $field_name != 'delta') {
        if ($field['type'] == 'list_text') {
          $data[$table_name][$field_name]['argument']['handler'] = 'views_handler_argument_field_list_string';
        }
        else {
          $data[$table_name][$field_name]['argument']['handler'] = 'views_handler_argument_field_list';
        }
      }
    }
  }
  return $data;
}
