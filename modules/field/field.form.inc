<?php

/**
 * @file
 * Field forms management.
 */

/**
 * Create a separate form element for each field.
 */
function field_default_form($entity_type, $entity, $field, $instance, $langcode, $items, &$form, &$form_state, $get_delta = NULL) {
  // This could be called with no entity, as when a UI module creates a
  // dummy form to set default values.
  if ($entity) {
    list($id, , ) = entity_extract_ids($entity_type, $entity);
  }

  $parents = $form['#parents'];

  $addition = array();
  $field_name = $field['field_name'];
  $addition[$field_name] = array();

  // Populate widgets with default values when creating a new entity.
  if (empty($items) && empty($id)) {
    $items = field_get_default_value($entity_type, $entity, $field, $instance, $langcode);
  }

  // Let modules alter the widget properties.
  $context = array(
    'entity_type' => $entity_type,
    'entity' => $entity,
    'field' => $field,
    'instance' => $instance,
  );
  drupal_alter(array('field_widget_properties', 'field_widget_properties_' . $entity_type), $instance['widget'], $context);

  // Collect widget elements.
  $elements = array();
  if (field_access('edit', $field, $entity_type, $entity)) {
    // Store field information in $form_state.
    if (!field_form_get_state($parents, $field_name, $langcode, $form_state)) {
      $field_state = array(
        'field' => $field,
        'instance' => $instance,
        'items_count' => count($items),
        'array_parents' => array(),
        'errors' => array(),
      );
      field_form_set_state($parents, $field_name, $langcode, $form_state, $field_state);
    }

    // If field module handles multiple values for this form element, and we
    // are displaying an individual element, process the multiple value form.
    if (!isset($get_delta) && field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
      $elements = field_multiple_value_form($field, $instance, $langcode, $items, $form, $form_state);
    }
    // If the widget is handling multiple values (e.g Options), or if we are
    // displaying an individual element, just get a single form element and
    // make it the $delta value.
    else {
      $delta = isset($get_delta) ? $get_delta : 0;
      $function = $instance['widget']['module'] . '_field_widget_form';
      if (function_exists($function)) {
        $element = array(
          '#entity_type' => $instance['entity_type'],
          '#bundle' => $instance['bundle'],
          '#field_name' => $field_name,
          '#language' => $langcode,
          '#field_parents' => $parents,
          '#columns' => array_keys($field['columns']),
          '#title' => check_plain($instance['label']),
          '#description' => field_filter_xss($instance['description']),
          // Only the first widget should be required.
          '#required' => $delta == 0 && $instance['required'],
          '#delta' => $delta,
        );
        if ($element = $function($form, $form_state, $field, $instance, $langcode, $items, $delta, $element)) {
          // Allow modules to alter the field widget form element.
          $context = array(
            'form' => $form,
            'field' => $field,
            'instance' => $instance,
            'langcode' => $langcode,
            'items' => $items,
            'delta' => $delta,
          );
          drupal_alter(array('field_widget_form', 'field_widget_' . $instance['widget']['type'] . '_form'), $element, $form_state, $context);

          // If we're processing a specific delta value for a field where the
          // field module handles multiples, set the delta in the result.
          // For fields that handle their own processing, we can't make
          // assumptions about how the field is structured, just merge in the
          // returned element.
          if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
            $elements[$delta] = $element;
          }
          else {
            $elements = $element;
          }
        }
      }
    }
  }

  if ($elements) {
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
      '#weight' => $instance['widget']['weight'],
    );
  }

  // Populate the 'array_parents' information in $form_state['field'] after
  // the form is built, so that we catch changes in the form structure performed
  // in alter() hooks.
  $elements['#after_build'][] = 'field_form_element_after_build';
  $elements['#field_name'] = $field_name;
  $elements['#language'] = $langcode;
  $elements['#field_parents'] = $parents;

  $addition[$field_name] += array(
    '#tree' => TRUE,
    // The '#language' key can be used to access the field's form element
    // when $langcode is unknown.
    '#language' => $langcode,
    $langcode => $elements,
  );

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
  $field_name = $field['field_name'];
  $parents = $form['#parents'];

  // Determine the number of widgets to display.
  switch ($field['cardinality']) {
    case FIELD_CARDINALITY_UNLIMITED:
      $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);
      $max = $field_state['items_count'];
      break;

    default:
      $max = $field['cardinality'] - 1;
      break;
  }

  $title = check_plain($instance['label']);
  $description = field_filter_xss($instance['description']);

  $id_prefix = implode('-', array_merge($parents, array($field_name)));
  $wrapper_id = drupal_html_id($id_prefix . '-add-more-wrapper');

  $field_elements = array();

  $function = $instance['widget']['module'] . '_field_widget_form';
  if (function_exists($function)) {
    for ($delta = 0; $delta <= $max; $delta++) {
      $multiple = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;
      $element = array(
        '#entity_type' => $instance['entity_type'],
        '#bundle' => $instance['bundle'],
        '#field_name' => $field_name,
        '#language' => $langcode,
        '#field_parents' => $parents,
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
            '#title' => t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
             // Note: this 'delta' is the FAPI 'weight' element's property.
            '#delta' => $max,
            '#default_value' => isset($items[$delta]['_weight']) ? $items[$delta]['_weight'] : $delta,
            '#weight' => 100,
          );
        }

        // Allow modules to alter the field widget form element.
        $context = array(
          'form' => $form,
          'field' => $field,
          'instance' => $instance,
          'langcode' => $langcode,
          'items' => $items,
          'delta' => $delta,
        );
        drupal_alter(array('field_widget_form', 'field_widget_' . $instance['widget']['type'] . '_form'), $element, $form_state, $context);

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
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name, $langcode))),
          '#submit' => array('field_add_more_submit'),
          '#ajax' => array(
            'callback' => 'field_add_more_js',
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
      }
    }
  }

  return $field_elements;
}

/**
 * Returns HTML for an individual form element.
 *
 * Combine multiple values into a table with drag-n-drop reordering.
 * TODO : convert to a template.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the form element.
 *
 * @ingroup themeable
 */
function theme_field_multiple_value_form($variables) {
  $element = $variables['element'];
  $output = '';

  if ($element['#cardinality'] > 1 || $element['#cardinality'] == FIELD_CARDINALITY_UNLIMITED) {
    $table_id = drupal_html_id($element['#field_name'] . '_values');
    $order_class = $element['#field_name'] . '-delta-order';
    $required = !empty($element['#required']) ? theme('form_required_marker', $variables) : '';

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
 * #after_build callback for field elements in a form.
 *
 * This stores the final location of the field within the form structure so
 * that field_default_form_errors() can assign validation errors to the right
 * form element.
 *
 * @see field_default_form_errors()
 */
function field_form_element_after_build($element, &$form_state) {
  $parents = $element['#field_parents'];
  $field_name = $element['#field_name'];
  $langcode = $element['#language'];

  $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);
  $field_state['array_parents'] = $element['#array_parents'];
  field_form_set_state($parents, $field_name, $langcode, $form_state, $field_state);

  return $element;
}

/**
 * Transfer field-level validation errors to widgets.
 */
function field_default_form_errors($entity_type, $entity, $field, $instance, $langcode, $items, $form, &$form_state) {
  $field_state = field_form_get_state($form['#parents'], $field['field_name'], $langcode, $form_state);

  if (!empty($field_state['errors'])) {
    $function = $instance['widget']['module'] . '_field_widget_error';
    $function_exists = function_exists($function);

    // Locate the correct element in the the form.
    $element = drupal_array_get_nested_value($form_state['complete form'], $field_state['array_parents']);

    $multiple_widget = field_behaviors_widget('multiple values', $instance) != FIELD_BEHAVIOR_DEFAULT;
    foreach ($field_state['errors'] as $delta => $delta_errors) {
      // For multiple single-value widgets, pass errors by delta.
      // For a multiple-value widget, all errors are passed to the main widget.
      $error_element = $multiple_widget ? $element : $element[$delta];
      foreach ($delta_errors as $error) {
        if ($function_exists) {
          $function($error_element, $error, $form, $form_state);
        }
        else {
          // Make sure that errors are reported (even incorrectly flagged) if
          // the widget module fails to implement hook_field_widget_error().
          form_error($error_element, $error['error']);
        }
      }
    }
    // Reinitialize the errors list for the next submit.
    $field_state['errors'] = array();
    field_form_set_state($form['#parents'], $field['field_name'], $langcode, $form_state, $field_state);
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
  $button = $form_state['triggering_element'];

  // Go one level up in the form, to the widgets container.
  $element = drupal_array_get_nested_value($form, array_slice($button['#array_parents'], 0, -1));
  $field_name = $element['#field_name'];
  $langcode = $element['#language'];
  $parents = $element['#field_parents'];

  // Increment the items count.
  $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);
  $field_state['items_count']++;
  field_form_set_state($parents, $field_name, $langcode, $form_state, $field_state);

  $form_state['rebuild'] = TRUE;
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
  $button = $form_state['triggering_element'];

  // Go one level up in the form, to the widgets container.
  $element = drupal_array_get_nested_value($form, array_slice($button['#array_parents'], 0, -1));
  $field_name = $element['#field_name'];
  $langcode = $element['#language'];
  $parents = $element['#field_parents'];

  $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);

  $field = $field_state['field'];
  if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED) {
    return;
  }

  // Add a DIV around the delta receiving the Ajax effect.
  $delta = $element['#max_delta'];
  $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
  $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

  return $element;
}

/**
 * Retrieves processing information about a field from $form_state.
 *
 * @param $parents
 *   The array of #parents where the field lives in the form.
 * @param $field_name
 *   The field name.
 * @param $langcode
 *   The language in which the field values are entered.
 * @param $form_state
 *   The form state.
 *
 * @return
 *   An array with the following key/data pairs:
 *   - field: the field definition array,
 *   - instance: the field instance definition array,
 *   - items_count: the number of widgets to display for the field,
 *   - array_parents: the location of the field's widgets within the $form
 *     structure. This entry is populated at '#after_build' time.
 *   - errors: the array of field validation errors reported on the field. This
 *     entry is populated at field_attach_form_validate() time.
 *
 * @see field_form_set_state()
 */
function field_form_get_state($parents, $field_name, $langcode, &$form_state) {
  $form_state_parents = _field_form_state_parents($parents, $field_name, $langcode);
  return drupal_array_get_nested_value($form_state, $form_state_parents);
}

/**
 * Stores processing information about a field in $form_state.
 *
 * @param $parents
 *   The array of #parents where the field lives in the form.
 * @param $field_name
 *   The field name.
 * @param $langcode
 *   The language in which the field values are entered.
 * @param $form_state
 *   The form state.
 * @param $field_state
 *   The array of data to store. See field_form_get_state() for the structure
 *   and content of the array.
 *
 * @see field_form_get_state()
 */
function field_form_set_state($parents, $field_name, $langcode, &$form_state, $field_state) {
  $form_state_parents = _field_form_state_parents($parents, $field_name, $langcode);
  drupal_array_set_nested_value($form_state, $form_state_parents, $field_state);
}

/**
 * Returns the location of processing information within $form_state.
 */
function _field_form_state_parents($parents, $field_name, $langcode) {
  // To ensure backwards compatibility on regular entity forms for widgets that
  // still access $form_state['field'][$field_name] directly,
  // - top-level fields (empty $parents) are placed directly under
  //   $form_state['fields'][$field_name].
  // - Other fields are placed under
  //   $form_state['field']['#parents'][...$parents...]['#fields'][$field_name]
  //   to avoid clashes between field names and $parents parts.
  // @todo Remove backwards compatibility in Drupal 8, and use a unique
  // $form_state['field'][...$parents...]['#fields'][$field_name] structure.
  if (!empty($parents)) {
    $form_state_parents = array_merge(array('#parents'), $parents, array('#fields'));
  }
  else {
    $form_state_parents = array();
  }
  $form_state_parents = array_merge(array('field'), $form_state_parents, array($field_name, $langcode));

  return $form_state_parents;
}

/**
 * Retrieves the field definition for a widget's helper callbacks.
 *
 * Widgets helper element callbacks (such as #process, #element_validate,
 * #value_callback, ...) should use field_widget_field() and
 * field_widget_instance() instead of field_info_field() and
 * field_info_instance() when they need to access field or instance properties.
 * See hook_field_widget_form() for more details.
 *
 * @param $element
 *   The structured array for the widget.
 * @param $form_state
 *   The form state.
 *
 * @return
 *   The $field definition array for the current widget.
 *
 * @see field_widget_instance()
 * @see hook_field_widget_form()
 */
function field_widget_field($element, $form_state) {
  $field_state = field_form_get_state($element['#field_parents'], $element['#field_name'], $element['#language'], $form_state);
  return $field_state['field'];
}

/**
 * Retrieves the instance definition array for a widget's helper callbacks.
 *
 * Widgets helper element callbacks (such as #process, #element_validate,
 * #value_callback, ...) should use field_widget_field() and
 * field_widget_instance() instead of field_info_field() and
 * field_info_instance() when they need to access field or instance properties.
 * See hook_field_widget_form() for more details.
 *
 * @param $element
 *   The structured array for the widget.
 * @param $form_state
 *   The form state.
 *
 * @return
 *   The $instance definition array for the current widget.
 *
 * @see field_widget_field()
 * @see hook_field_widget_form()
 */
function field_widget_instance($element, $form_state) {
  $field_state = field_form_get_state($element['#field_parents'], $element['#field_name'], $element['#language'], $form_state);
  return $field_state['instance'];
}
