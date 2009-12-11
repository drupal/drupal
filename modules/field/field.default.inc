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

function field_default_submit($obj_type, $object, $field, $instance, $langcode, &$items, $form, &$form_state) {
  $field_name = $field['field_name'];

  // Reorder items to account for drag-n-drop reordering.
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
    $items = _field_sort_items($field, $items);
  }

  // Filter out empty values.
  $items = field_set_empty($field, $items);
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
 * Invoke hook_field_formatter_prepare_view() on the relavant formatters.
 */
function field_default_prepare_view($obj_type, $objects, $field, $instances, $langcode, &$items, $options, $build_mode) {
  // Group objects, instances and items by formatter module.
  $modules = array();
  foreach ($instances as $id => $instance) {
    $display = $instance['display'][$build_mode];
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
 * Default field 'view' operation.
 *
 * @see field_attach_view()
 */
function field_default_view($obj_type, $object, $field, $instance, $langcode, $items, $build_mode) {
  list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);

  $addition = array();
  $display = $instance['display'][$build_mode];

  if ($display['type'] !== 'hidden') {
    if ($build_mode == 'search_index') {
      $display['label'] = 'hidden';
    }

    $element = array(
      '#theme' => 'field',
      '#weight' => $display['weight'],
      '#title' => t($instance['label']),
      '#access' => field_access('view', $field, $obj_type, $object),
      '#label_display' => $display['label'],
      '#build_mode' => $build_mode,
      '#language' => $langcode,
      '#field_name' => $field['field_name'],
      '#field_type' => $field['type'],
      '#object_type' => $obj_type,
      '#bundle' => $bundle,
      '#object' => $object,
      '#items' => $items,
      '#formatter' => $display['type'],
    );

    // Calling the formatter function through module_invoke() can have a
    // performance impact on pages with many fields and values.
    $formatter_function = $display['module'] . '_field_formatter';
    if (field_behaviors_formatter('multiple values', $display) == FIELD_BEHAVIOR_DEFAULT) {
      foreach ($items as $delta => $item) {
        $element[$delta] = $formatter_function($obj_type, $object, $field, $instance, $langcode, $display, $items, $delta);
        $element[$delta]['#weight'] = $delta;
      }
    }
    else {
      $element[0] = $formatter_function($obj_type, $object, $field, $instance, $langcode, $display, $items, 0);
    }

    $addition = array($field['field_name'] => $element);
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
