<?php

/**
 * @file
 * Field forms management.
 */

use Drupal\Component\Utility\NestedArray;

/**
 * Callback for usort() within field-multiple-value-form.html.twig.
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

