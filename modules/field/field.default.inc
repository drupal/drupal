<?php
// $Id: field.default.inc,v 1.35 2010/05/23 07:30:56 dries Exp $

/**
 * @file
 * Default 'implementations' of hook_field_*(): common field housekeeping.
 *
 * Those implementations are special, as field.module does not define any field
 * types. Those functions take care of default stuff common to all field types.
 * They are called through the _field_invoke_default() iterator, generally in
 * the corresponding field_attach_[operation]() function.
 */

function field_default_extract_form_values($entity_type, $entity, $field, $instance, $langcode, &$items, $form, &$form_state) {
  $field_name = $field['field_name'];

  if (isset($form_state['values'][$field_name][$langcode])) {
    $items = $form_state['values'][$field_name][$langcode];
    // Remove the 'value' of the 'add more' button.
    unset($items['add_more']);
  }
}

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
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param $errors
 *   The array of errors, keyed by field name and by value delta, that have
 *   already been reported for the entity. The function should add its errors
 *   to this array. Each error is an associative array, with the following
 *   keys and values:
 *   - 'error': an error code (should be a string, prefixed with the module name)
 *   - 'message': the human readable message to be displayed.
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
      'message' => t('%name: this field cannot hold more than @count values.', array('%name' => t($instance['label']), '@count' => $field['cardinality'])),
    );
  }
}

function field_default_submit($entity_type, $entity, $field, $instance, $langcode, &$items, $form, &$form_state) {
  $field_name = $field['field_name'];

  if (isset($form_state['values'][$field_name][$langcode])) {
    // Reorder items to account for drag-n-drop reordering.
    if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
      $items = _field_sort_items($field, $items);
    }
    // Filter out empty values.
    $items = _field_filter_items($field, $items);
  }
  elseif (!empty($entity->revision) && isset($form_state['entity']->{$field_name}[$langcode])) {
    // To ensure new revisions are created with all field values in all
    // languages, populate values not included in the form with the ones from
    // the original object. This covers:
    // - partial forms including only a subset of the fields,
    // - fields for which the user has no edit access,
    // - languages not involved in the form.
    $items = $form_state['entity']->{$field_name}[$langcode];
  }
}

/**
 * Default field 'insert' operation.
 *
 * Insert default value if no $entity->$field_name entry was provided.
 * This can happen with programmatic saves, or on form-based creation where
 * the current user doesn't have 'edit' permission for the field.
 */
function field_default_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // _field_invoke() populates $items with an empty array if the $entity has no
  // entry for the field, so we check on the $entity itself.
  // We also check that the current field translation is actually defined before
  // assigning it a default value. This way we ensure that only the intended
  // languages get a default value. Otherwise we could have default values for
  // not yet open languages.
  if (empty($entity) || !property_exists($entity, $field['field_name']) ||
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
 *   An array of entities being displayed, keyed by entity id.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   id.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   Array of field values already loaded for the entities, keyed by entity id.
 * @param $display
 *   Can be either:
 *   - the name of a view mode
 *   - or an array of display settings to use for display, as found in the
 *     'display' entry of $instance definitions.
*/
function field_default_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items, $display) {
  // Group entities, instances and items by formatter module.
  $modules = array();
  foreach ($instances as $id => $instance) {
    $display = is_string($display) ? $instance['display'][$display] : $display;
    if ($display['type'] !== 'hidden') {
      $module = $display['module'];
      $modules[$module] = $module;
      $grouped_entities[$module][$id] = $entities[$id];
      $grouped_instances[$module][$id] = $instance;
      $grouped_displays[$module][$id] = $display;
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
 * Builds a renderable array for field values.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities being displayed, keyed by entity id.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   id.
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
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  $addition = array();

  // Prepare incoming display specifications.
  if (is_string($display)) {
    $view_mode = $display;
    $display = $instance['display'][$view_mode];
  }
  else {
    $view_mode = '_custom_display';
  }

  if ($display['type'] !== 'hidden') {
    // We never want to index fields labels.
    if ($view_mode == 'search_index') {
      $display['label'] = 'hidden';
    }

    // Calling the formatter function through module_invoke() can have a
    // performance impact on pages with many fields and values.
    $function = $display['module'] . '_field_formatter_view';
    if (function_exists($function)) {
      $elements = $function($entity_type, $entity, $field, $instance, $langcode, $items, $display);

      if ($elements) {
        $info = array(
          '#theme' => 'field',
          '#weight' => $display['weight'],
          '#title' => t($instance['label']),
          '#access' => field_access('view', $field, $entity_type, $entity),
          '#label_display' => $display['label'],
          '#view_mode' => $view_mode,
          '#language' => $langcode,
          '#field_name' => $field['field_name'],
          '#field_type' => $field['type'],
          '#field_translatable' => $field['translatable'],
          '#entity_type' => $entity_type,
          '#bundle' => $bundle,
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

function field_default_prepare_translation($entity_type, $entity, $field, $instance, $langcode, &$items) {
  $addition = array();
  if (isset($entity->translation_source->$field['field_name'])) {
    $addition[$field['field_name']] = $entity->translation_source->$field['field_name'];
  }
  return $addition;
}
