<?php

/**
 * @file
 * Provide views data for image.module.
 */

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\field\FieldInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for image fields. Adds an image relationship to the default
 * field data.
 *
 * @see field_views_field_default_views_data()
 */
function image_field_views_data(FieldInterface $field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the target_id field.
    $data[$table_name][$field->getName() . '_target_id']['relationship'] = array(
      'id' => 'standard',
      'base' => 'file_managed',
      'base field' => 'target_id',
      'label' => t('image from !field_name', array('!field_name' => $field->getName())),
    );
  }

  return $data;
}

/**
 * Implements hook_field_views_data_views_data_alter().
 *
 * Views integration to provide reverse relationships on image fields.
 */
function image_field_views_data_views_data_alter(array &$data, FieldInterface $field) {
  $entity_type = $field->entity_type;
  $field_name = $field->getName();
  $entity_info = \Drupal::entityManager()->getDefinition($entity_type);
  $pseudo_field_name = 'reverse_' . $field_name . '_' . $entity_type;

  list($label) = field_views_field_label($entity_type, $field_name);

  $data['file_managed'][$pseudo_field_name]['relationship'] = array(
    'title' => t('@entity using @field', array('@entity' => $entity_info->getLabel(), '@field' => $label)),
    'help' => t('Relate each @entity with a @field set to the image.', array('@entity' => $entity_info->getLabel(), '@field' => $label)),
    'id' => 'entity_reverse',
    'field_name' => $field_name,
    'entity_type' => $entity_type,
    'field table' => FieldableDatabaseStorageController::_fieldTableName($field),
    'field field' => $field_name . '_target_id',
    'base' => $entity_info->getBaseTable(),
    'base field' => $entity_info->getKey('id'),
    'label' => t('!field_name', array('!field_name' => $field_name)),
    'join_extra' => array(
      0 => array(
        'field' => 'deleted',
        'value' => 0,
        'numeric' => TRUE,
      ),
    ),
  );
}
