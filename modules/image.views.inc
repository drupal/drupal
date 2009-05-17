<?php

/**
 * @file
 * Provide views data and handlers for image.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_field_views_data().
 *
 * Views integration for image fields. Adds an image relationship to the default
 * field data.
 *
 * @see field_views_field_default_views_data()
 */
function image_field_views_data($field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the fid field.
    $data[$table_name][$field['field_name'] . '_fid']['relationship'] = array(
      'handler' => 'views_handler_relationship',
      'base' => 'file_managed',
      'base field' => 'fid',
      'label' => t('image from !field_name', array('!field_name' => $field['field_name'])),
    );
  }

  return $data;
}

/**
 * Implements hook_field_views_data_views_data_alter().
 *
 * Views integration to provide reverse relationships on image fields.
 */
function image_field_views_data_views_data_alter(&$data, $field) {
  foreach ($field['bundles'] as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);
    $pseudo_field_name = 'reverse_' . $field['field_name'] . '_' . $entity_type;

    list($label, $all_labels) = field_views_field_label($field['field_name']);
    $entity = $entity_info['label'];
    if ($entity == t('Node')) {
      $entity = t('Content');
    }

    $data['file_managed'][$pseudo_field_name]['relationship'] = array(
      'title' => t('@entity using @field', array('@entity' => $entity, '@field' => $label)),
      'help' => t('Relate each @entity with a @field set to the image.', array('@entity' => $entity, '@field' => $label)),
      'handler' => 'views_handler_relationship_entity_reverse',
      'field_name' => $field['field_name'],
      'field table' => _field_sql_storage_tablename($field),
      'field field' => $field['field_name'] . '_fid',
      'base' => $entity_info['base table'],
      'base field' => $entity_info['entity keys']['id'],
      'label' => t('!field_name', array('!field_name' => $field['field_name'])),
      'join_extra' => array(
        0 => array(
          'field' => 'entity_type',
          'value' => $entity_type,
        ),
        1 => array(
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ),
      ),
    );
  }
}
