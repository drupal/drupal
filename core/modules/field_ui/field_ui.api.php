<?php

/**
 * @file
 * Hooks provided by the Field UI module.
 */

/**
 * @addtogroup field_types
 * @{
 */

/**
 * Add settings to a field settings form.
 *
 * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow the module
 * defining the field to add global settings (i.e. settings that do not depend
 * on the bundle or instance) to the field settings form. If the field already
 * has data, only include settings that are safe to change.
 *
 * @todo: Only the field type module knows which settings will affect the
 * field's schema, but only the field storage module knows what schema
 * changes are permitted once a field already has data. Probably we need an
 * easy way for a field type module to ask whether an update to a new schema
 * will be allowed without having to build up a fake $prior_field structure
 * for hook_field_update_forbid().
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 *
 * @return
 *   The form definition for the field settings.
 */
function hook_field_settings_form($field, $instance) {
  $settings = $field['settings'];
  $form['max_length'] = array(
    '#type' => 'number',
    '#title' => t('Maximum length'),
    '#default_value' => $settings['max_length'],
    '#required' => FALSE,
    '#min' => 1,
    '#description' => t('The maximum length of the field in characters. Leave blank for an unlimited size.'),
  );
  return $form;
}

/**
 * Add settings to an instance field settings form.
 *
 * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow the module
 * defining the field to add settings for a field instance.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 * @param array $form_state
  *   The form state of the (entire) configuration form.
 *
 * @return
 *   The form definition for the field instance settings.
 */
function hook_field_instance_settings_form($field, $instance, $form_state) {
  $settings = $instance['settings'];

  $form['text_processing'] = array(
    '#type' => 'radios',
    '#title' => t('Text processing'),
    '#default_value' => $settings['text_processing'],
    '#options' => array(
      t('Plain text'),
      t('Filtered text (user selects text format)'),
    ),
  );
  if ($field['type'] == 'text_with_summary') {
    $form['display_summary'] = array(
      '#type' => 'select',
      '#title' => t('Display summary'),
      '#options' => array(
        t('No'),
        t('Yes'),
      ),
      '#description' => t('Display the summary to allow the user to input a summary value. Hide the summary to automatically fill it with a trimmed portion from the main post.'),
      '#default_value' => !empty($settings['display_summary']) ? $settings['display_summary'] :  0,
    );
  }

  return $form;
}

/**
 * Alters the formatter settings form.
 *
 * @param $element
 *   Form array.
 * @param $form_state
 *   The form state of the (entire) configuration form.
 * @param $context
 *   An associative array with the following elements:
 *   - formatter: The formatter object.
 *   - field: The field structure being configured.
 *   - instance: The instance structure being configured.
 *   - view_mode: The view mode being configured.
 *   - form: The (entire) configuration form array.
 *
 * @see \Drupal\field_ui\DisplayOverView.
 */
function hook_field_formatter_settings_form_alter(&$element, &$form_state, $context) {
  // Add a 'mysetting' checkbox to the settings form for 'foo_field' fields.
  if ($context['field']['type'] == 'foo_field') {
    $element['mysetting'] = array(
      '#type' => 'checkbox',
      '#title' => t('My setting'),
      '#default_value' => $context['formatter']->getSetting('mysetting'),
    );
  }
}

/**
 * Alters the field formatter settings summary.
 *
 * @param $summary
 *   The summary.
 * @param $context
 *   An associative array with the following elements:
 *   - formatter: The formatter object.
 *   - field: The field structure being configured.
 *   - instance: The instance structure being configured.
 *   - view_mode: The view mode being configured.
 *
 * @see \Drupal\field_ui\DisplayOverView.
 */
function hook_field_formatter_settings_summary_alter(&$summary, $context) {
  // Append a message to the summary when an instance of foo_field has
  // mysetting set to TRUE for the current view mode.
  if ($context['field']['type'] == 'foo_field') {
    if ($context['formatter']->getSetting('mysetting')) {
      $summary[] = t('My setting enabled.');
    }
  }
}

/**
 * @} End of "addtogroup field_types".
 */
