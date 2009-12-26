<?php
// $Id$

/**
 * @file
 * Default 'implementations' of hook_field_*(): common field housekeeping.
 *
 * Those implementations are special, as field.module does not define any field
 * types. Those functions take care of default stuff common to all field types.
 * They are called through the _field_invoke_default() iterator, generally in
 * the corresponding field_attach_[operation]() function.
 */

function field_default_extract_form_values($obj_type, $object, $field, $instance, $langcode, &$items, $form, &$form_state) {
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
 * @param $obj_type
 *   The type of $object.
 * @param $object
 *   The object for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $object's bundle.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   $object->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param $errors
 *   The array of errors, keyed by field name and by value delta, that have
 *   already been reported for the object. The function should add its errors
 *   to this array. Each error is an associative array, with the following
 *   keys and values:
 *   - 'error': an error code (should be a string, prefixed with the module name)
 *   - 'message': the human readable message to be displayed.
 */
function field_default_validate($obj_type, $object, $field, $instance, $langcode, $items, &$errors) {
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

function field_default_submit($obj_type, $object, $field, $instance, $langcode, &$items, $form, &$form_state) {
  $field_name = $field['field_name'];

  // Reorder items to account for drag-n-drop reordering.
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
    $items = _field_sort_items($field, $items);
  }

  // Filter out empty values.
  $items = _field_filter_items($field, $items);
}

/**
 * Default field 'insert' operation.
 *
 * Insert default value if no $object->$field_name entry was provided.
 * This can happen with programmatic saves, or on form-based creation where
 * the current user doesn't have 'edit' permission for the field.
 */
function field_default_insert($obj_type, $object, $field, $instance, $langcode, &$items) {
  // _field_invoke() populates $items with an empty array if the $object has no
  // entry for the field, so we check on the $object itself.
  // We also check that the current field translation is actually defined before
  // assigning it a default value. This way we ensure that only the intended
  // languages get a default value. Otherwise we could have default values for
  // not yet open languages.
  if (empty($object) || !property_exists($object, $field['field_name']) ||
    (isset($object->{$field['field_name']}[$langcode]) && count($object->{$field['field_name']}[$langcode]) == 0)) {
    $items = field_get_default_value($obj_type, $object, $field, $instance, $langcode);
  }
}

/**
 * Invokes hook_field_formatter_prepare_view() on the relevant formatters.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects being displayed, keyed by object id.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each object, keyed by object
 *   id.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   Array of field values already loaded for the objects, keyed by object id.
 * @param $display
 *   Can be either:
 *   - the name of a build mode
 *   - or an array of display settings to use for display, as found in the
 *     'display' entry of $instance definitions.
*/
function field_default_prepare_view($obj_type, $objects, $field, $instances, $langcode, &$items, $display) {
  // Group objects, instances and items by formatter module.
  $modules = array();
  foreach ($instances as $id => $instance) {
    $display = is_string($display) ? $instance['display'][$display] : $display;
    if ($display['type'] !== 'hidden') {
      $module = $display['module'];
      $modules[$module] = $module;
      $grouped_objects[$module][$id] = $objects[$id];
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
      $function($obj_type, $grouped_objects[$module], $field, $grouped_instances[$module], $langcode, $grouped_items[$module], $grouped_displays[$module]);
    }
  }
}

/**
 * Builds a renderable array for field values.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects being displayed, keyed by object id.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each object, keyed by object
 *   id.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   Array of field values already loaded for the objects, keyed by object id.
 * @param $display
 *   Can be either:
 *   - the name of a build mode;
 *   - or an array of custom display settings, as found in the 'display' entry
 *     of $instance definitions.
 */
function field_default_view($obj_type, $object, $field, $instance, $langcode, $items, $display) {
  list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);

  $addition = array();

  // Prepare incoming display specifications.
  if (is_string($display)) {
    $build_mode = $display;
    $display = $instance['display'][$build_mode];
  }
  else {
    $build_mode = '_custom_display';
  }

  if ($display['type'] !== 'hidden') {
    // We never want to index fields labels.
    if ($build_mode == 'search_index') {
      $display['label'] = 'hidden';
    }

    // Calling the formatter function through module_invoke() can have a
    // performance impact on pages with many fields and values.
    $function = $display['module'] . '_field_formatter_view';
    if (function_exists($function)) {
      $elements = $function($obj_type, $object, $field, $instance, $langcode, $items, $display);
    }

    if ($elements) {
      $info = array(
        '#theme' => 'field',
        '#weight' => $display['weight'],
        '#title' => t($instance['label']),
        '#access' => field_access('view', $field, $obj_type, $object),
        '#label_display' => $display['label'],
        '#build_mode' => $build_mode,
        '#language' => $langcode,
        '#field_name' => $field['field_name'],
        '#field_type' => $field['type'],
        '#field_translatable' => $field['translatable'],
        '#object_type' => $obj_type,
        '#bundle' => $bundle,
        '#object' => $object,
        '#items' => $items,
        '#formatter' => $display['type']
      );

      $addition[$field['field_name']] = array_merge($info, $elements);
    }
  }

  return $addition;
}

function field_default_prepare_translation($obj_type, $object, $field, $instance, $langcode, &$items) {
  $addition = array();
  if (isset($object->translation_source->$field['field_name'])) {
    $addition[$field['field_name']] = $object->translation_source->$field['field_name'];
  }
  return $addition;
}
