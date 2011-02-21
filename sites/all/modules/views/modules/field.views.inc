<?php
// $Id: field.views.inc,v 1.1.2.17 2011/01/04 23:54:35 dereine Exp $

/**
 * @file
 * Provide Views data and handlers for field.module
 */

/**
 * @defgroup views_field_module field.module handlers
 *
 * @{
 */

/**
 * Implements hook_views_data()
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
      $data = array_merge($data, $result);
    }
  }

  return $data;
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

  // Build the relationships between the field table and the entity tables.
  foreach ($field['bundles'] as $entity => $bundles) {
    $entity_info = entity_get_info($entity);
    $entity_tables[$entity_info['base table']] = $entity;

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

  $add_fields = array('entity_type', 'delta', 'language', 'bundle');
  foreach ($field['columns'] as $column_name => $attributes) {
    $add_fields[] = _field_sql_storage_columnname($field['field_name'], $column_name);
  }

  // Add the field handler for this field.
  $title_short = $field['field_name'];
  foreach ($tables as $type => $table) {
    if ($type == FIELD_LOAD_CURRENT) {
      $group = t('Fields');
      $column = 'entity_id';
    }
    else {
      $group = t('Fields (historical data)');
      $column = 'revision_id';
    }

    $data[$table][$column] = array(
      'group' => $group,
      'title' => $title_short,
      'title short' => $title_short,
      'help' =>  t('Appears in: @bundles', array('@bundles' => implode(', ', $bundles_names))),
    );
    $data[$table][$column]['field'] = array(
      'field' => $column,
      'table' => $table,
      'handler' => 'views_handler_field_field',
      'click sortable' => TRUE,
      'field_name' => $field['field_name'],
      'additional fields' => $add_fields,
      'entity_tables' => $entity_tables,
      'field_info' => $field,
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

    // Note: we don't have a label available here, because we are at the field
    // level, not at the instance level.
    if (count($field['columns']) == 1 && $column == 'value') {
      $title = t('@label (!name)', array('@label' => $field['field_name'], '!name' => $field['field_name']));
      $title_short = $field['field_name'];
    }
    else {
      $title = t('@label (!name) - !column', array('@label' => $field['field_name'], '!name' => $field['field_name'], '!column' => $column));
      $title_short = t('@label-truncated - !column', array('@label-truncated' => $field['field_name'], '!column' => $column));
    }

    foreach ($tables as $type => $table) {
      $group = $type == FIELD_LOAD_CURRENT ? t('Fields') : t('Fields (historical data)');
      $column_real_name = $field['storage']['details']['sql'][$type][$table][$column];

      // Load all the fields from the table by default.
      $additional_fields = array_values($field['storage']['details']['sql'][$type][$table]);

      $data[$table][$column_real_name] = array(
        'group' => $group,
        'title' => $title,
        'title short' => $title_short,
        'help' =>  t('Appears in: @bundles', array('@bundles' => implode(', ', $bundles_names))),
      );

      $data[$table][$column_real_name]['argument'] = array(
        'field' => $column_real_name,
        'table' => $table,
        'handler' => $argument,
        'additional fields' => $additional_fields,
        'field_name' => $field['field_name'],
        'empty field name' => t('<No value>'),
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
        $title = t('@label (!name) - delta', array('@label' => $field['field_name'], '!name' => $field['field_name']));
        $title_short = t('!name - delta', array('!name' => $field['field_name']));

        $data[$table]['delta'] = array(
          'group' => $group,
          'title' => $title,
          'title short' => $title_short,
          'help' => t('Delta - Appears in: @bundles', array('@bundles' => implode(', ', $bundles_names))),
        );
        $data[$table]['delta']['argument'] = array(
          'field' => 'delta',
          'table' => $table,
          'handler' => 'views_handler_argument_numeric',
          'additional fields' => $additional_fields,
          'empty field name' => t('<No value>'),
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
      if (!in_array($field_name, array('table', 'entity_id', 'revision_id'))) {
        $data[$table_name][$field_name]['filter']['handler'] = 'views_handler_filter_field_list';
      }
    }
  }
  return $data;
}

/**
 * @}
 */
