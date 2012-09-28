<?php

/**
 * @file
 * Default 'implementations' of hook_field_*(): common field housekeeping.
 *
 * Those implementations are special, as field.module does not define any field
 * types. Those functions take care of default stuff common to all field types.
 * They are called through the _field_invoke_default() iterator, generally in
 * the corresponding field_attach_[operation]() function.
 */

/**
 * Generic field validation handler.
 *
 * Possible error codes:
 * - 'field_cardinality': The number of values exceeds the field cardinality.
 *
 * @see _hook_field_validate()
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
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
function field_default_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
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
 * Inserts a default value if no $entity->$field_name entry was provided.
 *
 * This can happen with programmatic saves, or on form-based creation where
 * the current user doesn't have 'edit' permission for the field. This is the
 * default field 'insert' operation.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field in $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   An array that this function will populate with default values.
 */
function field_default_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // _field_invoke() populates $items with an empty array if the $entity has no
  // entry for the field, so we check on the $entity itself.
  // We also check that the current field translation is actually defined before
  // assigning it a default value. This way we ensure that only the intended
  // languages get a default value. Otherwise we could have default values for
  // not yet open languages.
  if (empty($entity) || (!isset($entity->{$field['field_name']}[$langcode]) && !property_exists($entity, $field['field_name'])) ||
    (isset($entity->{$field['field_name']}[$langcode]) && count($entity->{$field['field_name']}[$langcode]) == 0)) {
    $items = field_get_default_value($entity_type, $entity, $field, $instance, $langcode);
  }
}

/**
 * Invokes hook_field_formatter_prepare_view() on the relevant formatters.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities being displayed, keyed by entity ID.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   ID.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   Array of field values already loaded for the entities, keyed by entity id.
 * @param $display
 *   Can be either:
 *   - The name of a view mode
 *   - An array of display settings to use for display, as found in the
 *     'display' entry of $instance definitions.
 */
function field_default_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items, $display) {
  // Group entities, instances and items by formatter module.
  $modules = array();
  foreach ($instances as $id => $instance) {
    if (is_string($display)) {
      $view_mode = $display;
      $instance_display = field_get_display($instance, $view_mode, $entities[$id]);
    }
    else {
      $instance_display = $display;
    }

    if ($instance_display['type'] !== 'hidden') {
      $module = $instance_display['module'];
      $modules[$module] = $module;
      $grouped_entities[$module][$id] = $entities[$id];
      $grouped_instances[$module][$id] = $instance;
      $grouped_displays[$module][$id] = $instance_display;
      // hook_field_formatter_prepare_view() alters $items by reference.
      $grouped_items[$module][$id] = &$items[$id];
    }
  }

  foreach ($modules as $module) {
    // Invoke hook_field_formatter_prepare_view().
    $function = $module . '_field_formatter_prepare_view';
    if (function_exists($function)) {
      $function($entity_type, $grouped_entities[$module], $field, $grouped_instances[$module], $langcode, $grouped_items[$module], $grouped_displays[$module]);
    }
  }
}

/**
 * Builds a renderable array for one field on one entity instance.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   A single object of type $entity_type.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   An array containing each field on $entity's bundle.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   Array of field values already loaded for the entities, keyed by entity id.
 * @param $display
 *   Can be either:
 *   - the name of a view mode;
 *   - or an array of custom display settings, as found in the 'display' entry
 *     of $instance definitions.
 */
function field_default_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $addition = array();

  // Prepare incoming display specifications.
  if (is_string($display)) {
    $view_mode = $display;
    $display = field_get_display($instance, $view_mode, $entity);
  }
  else {
    $view_mode = '_custom_display';
  }

  if ($display['type'] !== 'hidden') {
    // Calling the formatter function through module_invoke() can have a
    // performance impact on pages with many fields and values.
    $function = $display['module'] . '_field_formatter_view';
    if (function_exists($function)) {
      $elements = $function($entity_type, $entity, $field, $instance, $langcode, $items, $display);

      if ($elements) {
        $info = array(
          '#theme' => 'field',
          '#weight' => $display['weight'],
          '#title' => $instance['label'],
          '#access' => field_access('view', $field, $entity_type, $entity),
          '#label_display' => $display['label'],
          '#view_mode' => $view_mode,
          '#language' => $langcode,
          '#field_name' => $field['field_name'],
          '#field_type' => $field['type'],
          '#field_translatable' => $field['translatable'],
          '#entity_type' => $entity_type,
          '#bundle' => $entity->bundle(),
          '#object' => $entity,
          '#items' => $items,
          '#formatter' => $display['type']
        );

        $addition[$field['field_name']] = array_merge($info, $elements);
      }
    }
  }

  return $addition;
}

/**
 * Copies source field values into the entity to be prepared.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity to be prepared for translation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field in $entity's bundle.
 * @param $langcode
 *   The language the entity has to be translated to.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param $source_entity
 *   The source entity holding the field values to be translated.
 * @param $source_langcode
 *   The source language from which to translate.
 */
function field_default_prepare_translation($entity_type, $entity, $field, $instance, $langcode, &$items, $source_entity, $source_langcode) {
  $field_name = $field['field_name'];
  // If the field is untranslatable keep using LANGUAGE_NOT_SPECIFIED.
  if ($langcode == LANGUAGE_NOT_SPECIFIED) {
    $source_langcode = LANGUAGE_NOT_SPECIFIED;
  }
  if (isset($source_entity->{$field_name}[$source_langcode])) {
    $items = $source_entity->{$field_name}[$source_langcode];
  }
}
