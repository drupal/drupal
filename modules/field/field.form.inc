<?php
// $Id$

/**
 * @file
 * Field forms management.
 */

/**
 * Create a separate form element for each field.
 */
function field_default_form($obj_type, $object, $field, $instance, $langcode, $items, &$form, &$form_state, $get_delta = NULL) {
  // This could be called with no object, as when a UI module creates a
  // dummy form to set default values.
  if ($object) {
    list($id, , ) = entity_extract_ids($obj_type, $object);
  }

  $field_name = $field['field_name'];

  // Put field information at the top of the form, so that it can be easily
  // retrieved.
  // Note : widgets and other form handling code should *always* fetch field
  // and instance information from $form['#fields'] rather than from
  // field_info_field(). This lets us build forms for 'variants' of a field,
  // for instance on admin screens.
  $form['#fields'][$field_name] = array(
    'field' => $field,
    'instance' => $instance,
  );

  // Populate widgets with default values when creating a new object.
  if (empty($items) && empty($id)) {
    $items = field_get_default_value($obj_type, $object, $field, $instance, $langcode);
  }

  $field_elements = array();

  if (field_access('edit', $field, $obj_type, $object)) {
    // If field module handles multiple values for this form element, and we
    // are displaying an individual element, process the multiple value form.
    if (!isset($get_delta) && field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
      $field_elements = field_multiple_value_form($field, $instance, $langcode, $items, $form, $form_state);
    }
    // If the widget is handling multiple values (e.g Options), or if we are
    // displaying an individual element, just get a single form element and
    // make it the $delta value.
    else {
      $delta = isset($get_delta) ? $get_delta : 0;
      $function = $instance['widget']['module'] . '_field_widget_form';
      if (function_exists($function)) {
        $element = array(
          '#object_type' => $instance['object_type'],
          '#bundle' => $instance['bundle'],
          '#field_name' => $field_name,
          '#columns' => array_keys($field['columns']),
          '#title' => check_plain(t($instance['label'])),
          '#description' => field_filter_xss($instance['description']),
          // Only the first widget should be required.
          '#required' => $delta == 0 && $instance['required'],
          '#delta' => $delta,
        );
        if ($element = $function($form, $form_state, $field, $instance, $langcode, $items, $delta, $element)) {
          // If we're processing a specific delta value for a field where the
          // field module handles multiples, set the delta in the result.
          // For fields that handle their own processing, we can't make
          // assumptions about how the field is structured, just merge in the
          // returned element.
          if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
            $field_elements[$delta] = $element;
          }
          else {
            $field_elements = $element;
          }
        }
      }
    }
  }

  if ($field_elements) {
    // Add the field form element as a child keyed by language code to match
    // the field data structure:
    // $object->{$field_name}[$langcode][$delta][$column].
    // The '#language' key can be used to access the field's form element
    // when $langcode is unknown. The #weight property is inherited from the
    // field's form element.
    // Also aid in theming of field widgets by rendering a classified
    // container.
    $addition[$field_name] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'field-type-' . drupal_html_class($field['type']),
          'field-name-' . drupal_html_class($field_name),
          'field-widget-' . drupal_html_class($instance['widget']['type']),
        ),
      ),
      '#tree' => TRUE,
      '#weight' => $instance['widget']['weight'],
      '#language' => $langcode,
      $langcode => $field_elements,
    );
  }
  else {
    // The field is not accessible, or the widget did not return anything. Make
    // sure the items are available in the submitted form values.
    foreach ($items as $delta => $item) {
      $field_elements[$delta] = array(
        '#type' => 'value',
        '#value' => $item,
      );
    }
    $addition[$field_name] = array(
      '#tree' => TRUE,
      '#language' => $langcode,
      $langcode => $field_elements,
    );
  }

  $form['#fields'][$field_name]['form_path'] = array($field_name);

  return $addition;
}

/**
 * Special handling to create form elements for multiple values.
 *
 * Handles generic features for multiple fields:
 * - number of widgets
 * - AHAH-'add more' button
 * - drag-n-drop value reordering
 */
function field_multiple_value_form($field, $instance, $langcode, $items, &$form, &$form_state) {
  // This form has its own multistep persistance.
  if ($form_state['rebuild']) {
    $form_state['input'] = array();
  }

  $field_name = $field['field_name'];

  // Determine the number of widgets to display.
  switch ($field['cardinality']) {
    case FIELD_CARDINALITY_UNLIMITED:
      $filled_items = _field_filter_items($field, $items);
      $current_item_count = isset($form_state['field_item_count'][$field_name])
                            ? $form_state['field_item_count'][$field_name]
                            : count($items);
      // We always want at least one empty icon for the user to fill in.
      $max = ($current_item_count > count($filled_items))
              ? $current_item_count - 1
              : $current_item_count;

      break;
    default:
      $max = $field['cardinality'] - 1;
      break;
  }

  $title = check_plain(t($instance['label']));
  $description = field_filter_xss(t($instance['description']));
  $wrapper_id = drupal_html_class($field_name) . '-wrapper';
  $field_elements = array();

  $function = $instance['widget']['module'] . '_field_widget_form';
  if (function_exists($function)) {
    for ($delta = 0; $delta <= $max; $delta++) {
      $multiple = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;
      $element = array(
        '#object_type' => $instance['object_type'],
        '#bundle' => $instance['bundle'],
        '#field_name' => $field_name,
        '#columns' => array_keys($field['columns']),
        // For multiple fields, title and description are handled by the wrapping table.
        '#title' => $multiple ? '' : $title,
        '#description' => $multiple ? '' : $description,
        // Only the first widget should be required.
        '#required' => $delta == 0 && $instance['required'],
        '#delta' => $delta,
        '#weight' => $delta,
      );
      if ($element = $function($form, $form_state, $field, $instance, $langcode, $items, $delta, $element)) {
        // Input field for the delta (drag-n-drop reordering).
        if ($multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
             // Note: this 'delta' is the FAPI 'weight' element's property.
            '#delta' => $max,
            '#default_value' => isset($items[$delta]['_weight']) ? $items[$delta]['_weight'] : $delta,
            '#weight' => 100,
          );
        }
        $field_elements[$delta] = $element;
      }
    }

    if ($field_elements) {
      $field_elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field['field_name'],
        '#cardinality' => $field['cardinality'],
        '#title' => $title,
        '#required' => $instance['required'],
        '#description' => $description,
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#max_delta' => $max,
      );
      // Add 'add more' button, if not working with a programmed form.
      if ($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED && empty($form_state['programmed'])) {
        $field_elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => $field_name . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array($field_name, $langcode)),
          '#submit' => array('field_add_more_submit'),
          '#ajax' => array(
            'callback' => 'field_add_more_js',
            'wrapper' => $wrapper_id,
            'method' => 'replace',
            'effect' => 'fade',
          ),
          // The field_add_more_submit() and field_add_more_js() handlers will
          // find the relevant field using those entries.
          '#field_name' => $field_name,
          '#language' => $langcode,
        );
      }
    }
  }

  return $field_elements;
}

/**
 * Theme an individual form element.
 *
 * Combine multiple values into a table with drag-n-drop reordering.
 * TODO : convert to a template.
 */
function theme_field_multiple_value_form($variables) {
  $element = $variables['element'];
  $output = '';

  if ($element['#cardinality'] > 1 || $element['#cardinality'] == FIELD_CARDINALITY_UNLIMITED) {
    $table_id = $element['#field_name'] . '_values';
    $order_class = $element['#field_name'] . '-delta-order';
    $required = !empty($element['#required']) ? '<span class="form-required" title="' . t('This field is required. ') . '">*</span>' : '';

    $header = array(
      array(
        'data' => '<label>' . t('!title: !required', array('!title' => $element['#title'], '!required' => $required)) . "</label>",
        'colspan' => 2,
        'class' => array('field-label'),
      ),
      t('Order'),
    );
    $rows = array();

    // Sort items according to '_weight' (needed when the form comes back after
    // preview or failed validation)
    $items = array();
    foreach (element_children($element) as $key) {
      if ($key === 'add_more') {
        $add_more_button = &$element[$key];
      }
      else {
        $items[] = &$element[$key];
      }
    }
    usort($items, '_field_sort_items_value_helper');

    // Add the items as table rows.
    foreach ($items as $key => $item) {
      $item['_weight']['#attributes']['class'] = array($order_class);
      $delta_element = drupal_render($item['_weight']);
      $cells = array(
        array('data' => '', 'class' => array('field-multiple-drag')),
        drupal_render($item),
        array('data' => $delta_element, 'class' => array('delta-order')),
      );
      $rows[] = array(
        'data' => $cells,
        'class' => array('draggable'),
      );
    }

    $output = '<div class="form-item">';
    $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => $table_id, 'class' => array('field-multiple-table'))));
    $output .= $element['#description'] ? '<div class="description">' . $element['#description'] . '</div>' : '';
    $output .= '<div class="clearfix">' . drupal_render($add_more_button) . '</div>';
    $output .= '</div>';

    drupal_add_tabledrag($table_id, 'order', 'sibling', $order_class);
  }
  else {
    foreach (element_children($element) as $key) {
      $output .= drupal_render($element[$key]);
    }
  }

  return $output;
}


/**
 * Transfer field-level validation errors to widgets.
 */
function field_default_form_errors($obj_type, $object, $field, $instance, $langcode, $items, $form, $errors) {
  $field_name = $field['field_name'];
  if (!empty($errors[$field_name][$langcode])) {
    $function = $instance['widget']['module'] . '_field_widget_error';
    $function_exists = function_exists($function);

    // Walk the form down to where the widget lives.
    $form_path = $form['#fields'][$field_name]['form_path'];
    $element = $form;
    foreach ($form_path as $key) {
      $element = $element[$key];
    }

    $multiple_widget = field_behaviors_widget('multiple values', $instance) != FIELD_BEHAVIOR_DEFAULT;
    foreach ($errors[$field_name][$langcode] as $delta => $delta_errors) {
      // For multiple single-value widgets, pass errors by delta.
      // For a multiple-value widget, all errors are passed to the main widget.
      $error_element = $multiple_widget ? $element[$langcode] : $element[$langcode][$delta];
      foreach ($delta_errors as $error) {
        if ($function_exists) {
          $function($error_element, $error);
        }
        else {
          // Make sure that errors are reported (even incorrectly flagged) if
          // the widget module fails to implement hook_field_widget_error().
          form_error($error_element, $error['error']);
        }
      }
    }
  }
}

/**
 * Submit handler for the "Add another item" button of a field form.
 *
 * This handler is run regardless of whether JS is enabled or not. It makes
 * changes to the form state. If the button was clicked with JS disabled, then
 * the page is reloaded with the complete rebuilt form. If the button was
 * clicked with JS enabled, then ajax_form_callback() calls field_add_more_js()
 * to return just the changed part of the form.
 */
function field_add_more_submit($form, &$form_state) {
  // Set the form to rebuild and run submit handlers.
  if (isset($form['#builder_function']) && function_exists($form['#builder_function'])) {
    $entity = $form['#builder_function']($form, $form_state);

    // Make the changes we want to the form state.
    $field_name = $form_state['clicked_button']['#field_name'];
    $langcode = $form_state['clicked_button']['#language'];
    if ($form_state['values'][$field_name . '_add_more']) {
      $form_state['field_item_count'][$field_name] = count($form_state['values'][$field_name][$langcode]);
    }
  }
}

/**
 * Ajax callback in response to a new empty widget being added to the form.
 *
 * This returns the new page content to replace the page content made obsolete
 * by the form submission.
 *
 * @see field_add_more_submit()
 */
function field_add_more_js($form, $form_state) {
  // Retrieve field information.
  $field_name = $form_state['clicked_button']['#field_name'];
  $field = $form['#fields'][$field_name]['field'];
  if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED) {
    ajax_render(array());
  }
  // Navigate to the right part of the form.
  $form_path = $form['#fields'][$field_name]['form_path'];
  $field_form = $form;
  foreach ($form_path as $key) {
    $field_form = $field_form[$key];
  }

  // Add a DIV around the new field to receive the AJAX effect.
  $langcode = $field_form['#language'];
  $delta = $field_form[$langcode]['#max_delta'];
  $field_form[$langcode][$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($field_form[$langcode][$delta]['#prefix']) ? $field_form[$langcode][$delta]['#prefix'] : '');
  $field_form[$langcode][$delta]['#suffix'] = (isset($field_form[$langcode][$delta]['#suffix']) ? $field_form[$langcode][$delta]['#suffix'] : '') . '</div>';

  return drupal_render($field_form);
}
