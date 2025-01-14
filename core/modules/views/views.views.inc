<?php

/**
 * @file
 */

use Drupal\field\FieldConfigInterface;

/**
 * Returns the label of a certain field.
 *
 * Therefore it looks up in all bundles to find the most used field.
 */
function views_entity_field_label($entity_type, $field_name) {
  $label_counter = [];
  $all_labels = [];
  // Count the amount of fields per label per field storage.
  $entity_field_manager = \Drupal::service('entity_field.manager');
  foreach (array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type)) as $bundle) {
    $bundle_fields = array_filter($entity_field_manager->getFieldDefinitions($entity_type, $bundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });
    if (isset($bundle_fields[$field_name])) {
      $field = $bundle_fields[$field_name];
      $label = $field->getLabel();
      $label_counter[$label] = isset($label_counter[$label]) ? ++$label_counter[$label] : 1;
      $all_labels[$label] = TRUE;
    }
  }
  if (empty($label_counter)) {
    return [$field_name, $all_labels];
  }
  // Sort the field labels by it most used label and return the most used one.
  // If the counts are equal, sort by the label to ensure the result is
  // deterministic.
  uksort($label_counter, function ($a, $b) use ($label_counter) {
    if ($label_counter[$a] === $label_counter[$b]) {
      return strcmp($a, $b);
    }
    return $label_counter[$b] <=> $label_counter[$a];
  });
  $label_counter = array_keys($label_counter);
  return [$label_counter[0], $all_labels];
}
