<?php

/**
 * @file
 * Field forms management.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Returns HTML for an individual form element.
 *
 * Combines multiple values into a table with drag-n-drop reordering.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the form element.
 *
 * @ingroup themeable
 *
 * @todo Convert to a template.
 */
function theme_field_multiple_value_form($variables) {
  $element = $variables['element'];
  $output = '';

  if ($element['#cardinality_multiple']) {
    $form_required_marker = array('#theme' => 'form_required_marker');
    $required = !empty($element['#required']) ? drupal_render($form_required_marker) : '';
    $table_id = drupal_html_id($element['#field_name'] . '_values');
    $order_class = $element['#field_name'] . '-delta-order';

    $header = array(
      array(
        'data' => '<h4 class="label">' . t('!title !required', array('!title' => $element['#title'], '!required' => $required)) . "</h4>",
        'colspan' => 2,
        'class' => array('field-label'),
      ),
      t('Order', array(), array('context' => 'Sort order')),
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
    foreach ($items as $item) {
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

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => $table_id,
        'class' => array('field-multiple-table'),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ),
      ),
    );
    $output = '<div class="form-item">';
    $output .= drupal_render($table);
    $output .= $element['#description'] ? '<div class="description">' . $element['#description'] . '</div>' : '';
    $output .= '<div class="clearfix">' . drupal_render($add_more_button) . '</div>';
    $output .= '</div>';
  }
  else {
    foreach (element_children($element) as $key) {
      $output .= drupal_render($element[$key]);
    }
  }

  return $output;
}

/**
 * Callback for usort() within theme_field_multiple_value_form().
 *
 * Sorts using ['_weight']['#value']
 */
function _field_sort_items_value_helper($a, $b) {
  $a_weight = (is_array($a) && isset($a['_weight']['#value']) ? $a['_weight']['#value'] : 0);
  $b_weight = (is_array($b) && isset($b['_weight']['#value']) ? $b['_weight']['#value'] : 0);
  return $a_weight - $b_weight;
}

/**
 * After-build callback for field elements in a form.
 *
 * This stores the final location of the field within the form structure so that
 * field_default_form_errors() can assign validation errors to the right form
 * element.
 *
 * @param $element
 *   The form element.
 * @param $form_state
 *   An associative array containing the current state of the form.
 *
 * @return
 *   The $element array that was passed in as a parameter.
 *
 * @see field_default_form_errors()
 */
function field_form_element_after_build($element, &$form_state) {
  $parents = $element['#field_parents'];
  $field_name = $element['#field_name'];

  $field_state = field_form_get_state($parents, $field_name, $form_state);
  $field_state['array_parents'] = $element['#array_parents'];
  field_form_set_state($parents, $field_name, $form_state, $field_state);

  return $element;
}

/**
 * Retrieves processing information about a field from $form_state.
 *
 * @param array $parents
 *   The array of #parents where the field lives in the form.
 * @param $field_name
 *   The field name.
 * @param array $form_state
 *   The form state.
 *
 * @return
 *   An array with the following key/data pairs:
 *   - items_count: The number of widgets to display for the field.
 *   - array_parents: The location of the field's widgets within the $form
 *     structure. This entry is populated at '#after_build' time.
 *   - constraint_violations: The array of validation errors reported on the
 *     field. This entry is populated at form validate time.
 *
 * @see field_form_set_state()
 */
function field_form_get_state(array $parents, $field_name, array &$form_state) {
  $form_state_parents = _field_form_state_parents($parents, $field_name);
  return NestedArray::getValue($form_state, $form_state_parents);
}

/**
 * Stores processing information about a field in $form_state.
 *
 * @param array $parents
 *   The array of #parents where the field lives in the form.
 * @param $field_name
 *   The field name.
 * @param array $form_state
 *   The form state.
 * @param array $field_state
 *   The array of data to store. See field_form_get_state() for the structure
 *   and content of the array.
 *
 * @see field_form_get_state()
 */
function field_form_set_state(array $parents, $field_name, array &$form_state, array $field_state) {
  $form_state_parents = _field_form_state_parents($parents, $field_name);
  NestedArray::setValue($form_state, $form_state_parents, $field_state);
}

/**
 * Returns the location of processing information within $form_state.
 *
 * @param array $parents
 *   The array of #parents where the field lives in the form.
 * @param $field_name
 *   The field name.
 *
 * @return
 *   The location of processing information within $form_state.
 */
function _field_form_state_parents(array $parents, $field_name) {
  // Field processing data is placed at
  // $form_state['field']['#parents'][...$parents...]['#fields'][$field_name],
  // to avoid clashes between field names and $parents parts.
  return array_merge(array('field', '#parents'), $parents, array('#fields', $field_name));
}

