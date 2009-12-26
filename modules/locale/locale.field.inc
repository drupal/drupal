<?php
// $Id: locale.field.inc,v 1.6 2009/12/26 16:50:09 dries Exp $

/**
 * @file
 * Field API multilingual handling.
 */

/**
 * Form submit handler for node_form().
 *
 * Update the field language according to the node language, changing the
 * previous language if necessary.
 */
function locale_field_node_form_update_field_language($form, &$form_state, $reset_previous = TRUE) {
  $node = (object) $form_state['values'];
  $available_languages = field_multilingual_content_languages();
  // @todo: Unify language neutral language codes.
  $selected_language = empty($node->language) ? LANGUAGE_NONE : $node->language;
  list(, , $bundle) = entity_extract_ids('node', $node);

  foreach (field_info_instances('node', $bundle) as $instance) {
    $field_name = $instance['field_name'];
    $field = field_info_field($field_name);
    $previous_language = $form[$field_name]['#language'];

    // Handle a possible language change: previous language values are deleted,
    // new ones are inserted.
    if ($field['translatable'] && $previous_language != $selected_language) {
      $form_state['values'][$field_name][$selected_language] = $node->{$field_name}[$previous_language];
      if ($reset_previous) {
        $form_state['values'][$field_name][$previous_language] = array();
      }
    }
  }
}

/**
 * Apply fallback rules to the given object.
 *
 * Parameters are the same of hook_field_attach_view().
 */
function locale_field_fallback_view(&$output, $context) {
  // Lazily init fallback values and candidates to avoid unnecessary calls.
  $fallback_values = array();
  $fallback_candidates = NULL;
  list(, , $bundle) = entity_extract_ids($context['obj_type'], $context['object']);

  foreach (field_info_instances($context['obj_type'], $bundle) as $instance) {
    $field_name = $instance['field_name'];
    $field = field_info_field($field_name);

    // If the items array is empty then we have a missing field translation.
    // @todo: Verify this assumption.
    if (isset($output[$field_name]) && count(element_children($output[$field_name])) == 0) {
      if (!isset($fallback_candidates)) {
        require_once DRUPAL_ROOT . '/includes/language.inc';
        $fallback_candidates = language_fallback_get_candidates();
      }

      foreach ($fallback_candidates as $langcode) {
        // Again if we have a non-empty array we assume the field translation is
        // valid.
        if (!empty($context['object']->{$field_name}[$langcode])) {
          // Cache fallback values per language as fields might have different
          // fallback values.
          if (!isset($fallback_values[$langcode])) {
            $fallback_values[$langcode] = field_attach_view($context['obj_type'], $context['object'], $context['view_mode'], $langcode);
          }
          // We are done, skip to the next field.
          $output[$field_name] = $fallback_values[$langcode][$field_name];
          break;
        }
      }
    }
  }
}
