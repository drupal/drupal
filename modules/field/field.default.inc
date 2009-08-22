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
    unset($items[$field_name . '_add_more']);
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
 * Default field 'view' operation.
 *
 * @see field_attach_view()
 */
function field_default_view($obj_type, $object, $field, $instance, $langcode, $items, $build_mode) {
  list($id, $vid, $bundle) = field_attach_extract_ids($obj_type, $object);

  $addition = array();
  $display = $instance['display'][$build_mode];

  if ($display['type'] !== 'hidden') {
    $theme = 'field_formatter_' . $display['type'];
    $single = (field_behaviors_formatter('multiple values', $display) == FIELD_BEHAVIOR_DEFAULT);

    $label_display = $display['label'];
    if ($build_mode == 'search_index') {
      $label_display = 'hidden';
    }

    $info = array(
      '#field_name' => $field['field_name'],
      '#bundle' => $bundle,
      '#object' => $object,
      '#object_type' => $obj_type,
    );

    $element = $info + array(
      '#theme' => 'field',
      '#weight' => $display['weight'],
      '#title' => check_plain(t($instance['label'])),
      '#access' => field_access('view', $field),
      '#label_display' => $label_display,
      '#build_mode' => $build_mode,
      '#single' => $single,
      '#language' => $langcode,
      'items' => array(),
    );

    // Fill-in items.
    foreach ($items as $delta => $item) {
      $element['items'][$delta] = array(
        '#item' => $item,
        '#weight' => $delta,
      );
    }

    // Append formatter information either on each item ('single-value' formatter)
    // or at the upper 'items' level ('multiple-value' formatter)
    $format_info = $info + array(
      '#formatter' => $display['type'],
      '#settings' => $display['settings'],
      '#theme' => $theme,
    );

    if ($single) {
      foreach ($items as $delta => $item) {
        $element['items'][$delta] += $format_info;
        $element['items'][$delta]['#item']['#delta'] = $delta;
      }
    }
    else {
      $element['items'] += $format_info;
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
