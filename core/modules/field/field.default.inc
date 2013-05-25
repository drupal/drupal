<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * @file
 * Default 'implementations' of hook_field_*(): common field housekeeping.
 *
 * Those implementations are special, as field.module does not define any field
 * types. Those functions take care of default stuff common to all field types.
 * They are called through the _field_invoke_default() iterator, generally in
 * the corresponding field_attach_[operation]() function.
 */

use Drupal\Core\Language\Language;

/**
 * Generic field validation handler.
 *
 * Possible error codes:
 * - 'field_cardinality': The number of values exceeds the field cardinality.
 *
 * @see _hook_field_validate()
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field in $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param $errors
 *   The array of errors, keyed by field name and by value delta, that have
 *   already been reported for the entity. The function should add its errors to
 *   this array. Each error is an associative array, with the following keys and
 *   values:
 *   - error: An error code (should be a string, prefixed with the module name).
 *   - message: The human readable message to be displayed.
 */
function field_default_validate(EntityInterface $entity, $field, $instance, $langcode, $items, &$errors) {
  // Filter out empty values.
  $items = _field_filter_items($field, $items);

  // Check that the number of values doesn't exceed the field cardinality.
  // For form submitted values, this can only happen with 'multiple value'
  // widgets.
  if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED && count($items) > $field['cardinality']) {
    $errors[$field['field_name']][$langcode][0][] = array(
      'error' => 'field_cardinality',
      'message' => t('%name: this field cannot hold more than @count values.', array('%name' => $instance['label'], '@count' => $field['cardinality'])),
    );
  }
}

/**
 * Copies source field values into the entity to be prepared.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to be prepared for translation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field in $entity's bundle.
 * @param $langcode
 *   The language the entity has to be translated to.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param \Drupal\Core\Entity\EntityInterface $source_entity
 *   The source entity holding the field values to be translated.
 * @param $source_langcode
 *   The source language from which to translate.
 */
function field_default_prepare_translation(EntityInterface $entity, $field, $instance, $langcode, &$items, EntityInterface $source_entity, $source_langcode) {
  $field_name = $field['field_name'];
  // If the field is untranslatable keep using Language::LANGCODE_NOT_SPECIFIED.
  if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
    $source_langcode = Language::LANGCODE_NOT_SPECIFIED;
  }
  if (isset($source_entity->{$field_name}[$source_langcode])) {
    $items = $source_entity->{$field_name}[$source_langcode];
  }
}
