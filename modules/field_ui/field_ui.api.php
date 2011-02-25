<?php

/**
 * @file
 * Hooks provided by the Field UI module.
 */

/**
 * @ingroup field_ui_field_type
 * @{
 */

/**
 * Add settings to a field settings form.
 *
 * Invoked from field_ui_field_settings_form() to allow the module defining the
 * field to add global settings (i.e. settings that do not depend on the bundle
 * or instance) to the field settings form. If the field already has data, only
 * include settings that are safe to change.
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
 * @param $has_data
 *   TRUE if the field already has data, FALSE if not.
 *
 * @return
 *   The form definition for the field settings.
 */
function hook_field_settings_form($field, $instance, $has_data) {
  $settings = $field['settings'];
  $form['max_length'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum length'),
    '#default_value' => $settings['max_length'],
    '#required' => FALSE,
    '#element_validate' => array('_element_validate_integer_positive'),
    '#description' => t('The maximum length of the field in characters. Leave blank for an unlimited size.'),
  );
  return $form;
}

/**
 * Add settings to an instance field settings form.
 *
 * Invoked from field_ui_field_edit_form() to allow the module defining the
 * field to add settings for a field instance.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 *
 * @return
 *   The form definition for the field instance settings.
 */
function hook_field_instance_settings_form($field, $instance) {
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
      '#description' => t('Display the summary to allow the user to input a summary value. Hide the summary to automatically fill it with a trimmed portion from the main post. '),
      '#default_value' => !empty($settings['display_summary']) ? $settings['display_summary'] :  0,
    );
  }

  return $form;
}

/**
 * Add settings to a widget settings form.
 *
 * Invoked from field_ui_field_edit_form() to allow the module defining the
 * widget to add settings for a widget instance.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 *
 * @return
 *   The form definition for the widget settings.
 */
function hook_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  if ($widget['type'] == 'text_textfield') {
    $form['size'] = array(
      '#type' => 'textfield',
      '#title' => t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#element_validate' => array('_element_validate_integer_positive'),
      '#required' => TRUE,
    );
  }
  else {
    $form['rows'] = array(
      '#type' => 'textfield',
      '#title' => t('Rows'),
      '#default_value' => $settings['rows'],
      '#element_validate' => array('_element_validate_integer_positive'),
      '#required' => TRUE,
    );
  }

  return $form;
}


/**
 * Returns form elements for a formatter's settings.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 * @param $view_mode
 *   The view mode being configured.
 * @param $form
 *   The (entire) configuration form array, which will usually have no use here.
 * @param $form_state
 *   The form state of the (entire) configuration form.
 *
 * @return
 *   The form elements for the formatter settings.
 */
function hook_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $element = array();

  if ($display['type'] == 'text_trimmed' || $display['type'] == 'text_summary_or_trimmed') {
    $element['trim_length'] = array(
      '#title' => t('Length'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['trim_length'],
      '#element_validate' => array('_element_validate_integer_positive'),
      '#required' => TRUE,
    );
  }

  return $element;

}

/**
 * Returns a short summary for the current formatter settings of an instance.
 *
 * If an empty result is returned, the formatter is assumed to have no
 * configurable settings, and no UI will be provided to display a settings
 * form.
 *
 * @param $field
 *   The field structure.
 * @param $instance
 *   The instance structure.
 * @param $view_mode
 *   The view mode for which a settings summary is requested.
 *
 * @return
 *   A string containing a short summary of the formatter settings.
 */
function hook_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $summary = '';

  if ($display['type'] == 'text_trimmed' || $display['type'] == 'text_summary_or_trimmed') {
    $summary = t('Length: @chars chars', array('@chars' => $settings['trim_length']));
  }

  return $summary;
}

/**
 * @} End of "ingroup field_ui_field_type"
 */
