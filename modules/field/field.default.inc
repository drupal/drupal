<?php

/**
 * @file
 *
 * Default 'implementations' of hook_field_*().
 *
 * Handles common field housekeeping.
 * Those implementations are special, as field.module does not define any field
 * types. Those functions take care of default stuff common to all field types.
 *
 * Storage ops ('load', 'insert', 'update', 'delete', 'delete revisions')
 * are not executed field by field, and are thus handled separately.
 */

function field_default_validate($obj_type, $object, $field, $instance, $items, $form) {
  // TODO: here we could validate that required fields are filled in (for programmatic save)
}

function field_default_submit($obj_type, &$object, $field, $instance, &$items, $form, &$form_state) {
  // Get field values from the submitted form values. Assigning them to $items
  // populates $object->field_name when we return from _field_invoke_default().

  // TODO D7: Allow the values to be form_altered to another location, like we
  // do for the form definition ($form['#fields'][$field_name]['form_path']) ?

  if (isset($form_state['values'][$field['field_name']])) {
    $items = $form_state['values'][$field['field_name']];

    // Remove the 'value' of the 'add more' button.
    unset($items[$field['field_name'] . '_add_more']);

    // TODO: the above should be moved to validate time (and values saved back
    // using form_set_value() ), so that hook_field_validate() works on clean data.
    // Not sure we'll want what's below in validate too.

    // Reorder items to account for drag-n-drop reordering.
    if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
      $items = _field_sort_items($field, $items);
    }

    // Filter out empty values.
    $items = field_set_empty($field, $items);

    // _field_invoke() does not add back items for fields not present in the
    // original $object, so add them manually.
    $object->{$field['field_name']} = $items;
  }
  else {
    // The form did not include this field, for instance because of access
    // rules: make sure any existing value for the field stays unchanged.
    unset($object->{$field['field_name']});
  }
}

/**
 * The 'view' operation constructs the $object in a way that you can use
 * drupal_render() to display the formatted output for an individual field.
 * i.e. print drupal_render($object->content['field_foo']);
 *
 * The code supports both single value formatters, which theme an individual
 * item value, and multiple value formatters, which theme all values for the
 * field in a single theme. The multiple value formatters could be used, for
 * instance, to plot field values on a single map or display them in a graph.
 * Single value formatters are the default, multiple value formatters can be
 * designated as such in formatter_info().
 *
 * The $object array will look like:
 *   $object->content['field_foo']['wrapper'] = array(
 *     '#type' => 'field',
 *     '#title' => 'label'
 *     '#field_name' => 'field_name',
 *     '#object' => $object,
 *     '#object_type' => $obj_type,
 *     // Value of the $teaser param of hook_nodeapi('view').
 *     '#teaser' => $teaser,
 *     'items' =>
 *       0 => array(
 *         '#item' => $items[0],
 *         // Only for 'single-value' formatters
 *         '#theme' => $theme,
 *         '#field_name' => 'field_name',
 *         '#bundle' => $bundle,
 *         '#formatter' => $formatter_name,
 *         '#settings' => $formatter_settings,
 *         '#object' => $object,
 *         '#object_type' => $obj_type,
 *         '#delta' => 0,
 *       ),
 *       1 => array(
 *         '#item' => $items[1],
 *         // Only for 'single-value' formatters
 *         '#theme' => $theme,
 *         '#field_name' => 'field_name',
 *         '#bundle' => $bundle_name,
 *         '#formatter' => $formatter_name,
 *         '#settings' => $formatter_settings,
 *         '#object' => $object,
 *         '#object_type' => $obj_type,
 *         '#delta' => 1,
 *       ),
 *       // Only for 'multiple-value' formatters
 *       '#theme' => $theme,
 *       '#field_name' => 'field_name',
 *       '#bundle' => $bundle_name,
 *       '#formatter' => $formatter_name,
 *       '#settings' => $formatter_settings,
 *     ),
 *   );
 */
function field_default_view($obj_type, $object, $field, $instance, $items, $teaser) {
  list($id, $vid, $bundle) = field_attach_extract_ids($obj_type, $object);

  $addition = array();

  // Entities without build modes should provide a 'full' context.
  // NODE_BUILD_NORMAL is 0, and ('whatever' == 0) is TRUE, so we need a ===.
  if (!isset($object->build_mode)) {
    $context = 'full';
  }
  elseif ($object->build_mode === NODE_BUILD_NORMAL
    || $object->build_mode == NODE_BUILD_PREVIEW) {
    $context = $teaser ? 'teaser' : 'full';
  }
  else {
    $context = $object->build_mode;
  }

  // If we don't have specific settings for the current build_mode, we use the
  // (required) 'full' build_mode.
  $display = isset($instance['display'][$context]) ? $instance['display'][$context] : $instance['display']['full'];
  // Ensure we have a valid formatter and formatter settings.
  $display = _field_get_formatter($display, $field);

  if ($display['type'] && $display['type'] !== 'hidden') {
    $theme = 'field_formatter_' . $display['type'];
    $single = (field_behaviors_formatter('multiple values', $display) == FIELD_BEHAVIOR_DEFAULT);

    $label_display = $display['label'];
    if (isset($object->build_mode) && $object->build_mode == NODE_BUILD_SEARCH_INDEX) {
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
      '#title' => check_plain(t($instance['label'])),
      '#access' => field_access('view', $field),
      '#label_display' => $label_display,
      '#teaser' => $teaser,
      '#single' => $single,
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
      '#theme_wrapper' => $theme,
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

    // The wrapper lets us get the themed output for the whole field
    // to populate the $FIELD_NAME_rendered variable for templates,
    // and hide it from the $content variable if needed.
    // See 'preprocess' op and theme_content_field_wrapper()?
    $wrapper = $info + array(
      'field' => $element,
      '#weight' => $instance['weight'],
      '#post_render' => array('field_wrapper_post_render'),
      '#context' => $context,
    );

    $addition = array($field['field_name'] => $wrapper);
  }
  return $addition;
}

/**
 * Hide excluded fields from the $content variable in templates.
 */
function field_wrapper_post_render($content, $element) {
  $instance = field_info_instance($element['#field_name'], $element['#bundle']);
  if (theme('field_exclude', $content, $instance, $element['#context'])) {
    return '';
  }
  return $content;
}

/**
 * 'Theme' function for a field's addition to the combined template output,
 * i.e. the node's $content or the user's $user_profile value.
 * This allows more flexibility in templates : you can use custom markup
 * around a few specific fields, and print the rest normally.
 *
 * This is a theme function, so it can be overridden in different
 * themes to produce different results.
 *
 * The html for individual fields and groups are available in the
 * $FIELD_NAME_rendered and $GROUP_NAME_rendered variables.
 *
 * @return
 *   Whether or not the field's content is to be added in this context.
 *   Uses the 'exclude' value from the field's display settings.
 */
function theme_field_exclude($content, $object, $context) {
  if (empty($object['display'])
    || empty($object['display'][$context])
    || empty($object['display'][$context]['exclude'])) {
    return FALSE;
  }
  else {
    return TRUE;
  }
}

function field_default_preprocess($obj_type, $object, $field, $instance, &$items) {
  return array(
    $field['field_name'] . '_rendered' => isset($object->content[$field['field_name']]['#children']) ? $object->content[$field['field_name']]['#children'] : '',
  );
}

function field_default_prepare_translation($obj_type, $object, $field, $instance, &$items) {
  $addition = array();
  if (isset($object->translation_source->$field['field_name'])) {
    $addition[$field['field_name']] = $object->translation_source->$field['field_name'];
  }
  return $addition;
}