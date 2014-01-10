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
 * Alters the formatter settings form.
 *
 * @param $element
 *   Form array.
 * @param $form_state
 *   The form state of the (entire) configuration form.
 * @param $context
 *   An associative array with the following elements:
 *   - formatter: The formatter object.
 *   - field_definition: The field definition.
 *   - view_mode: The view mode being configured.
 *   - form: The (entire) configuration form array.
 *
 * @see \Drupal\field_ui\DisplayOverView.
 */
function hook_field_formatter_settings_form_alter(&$element, &$form_state, $context) {
  // Add a 'mysetting' checkbox to the settings form for 'foo_formatter'
  // field formatters.
  if ($context['formatter']->getPluginId() == 'foo_formatter') {
    $element['mysetting'] = array(
      '#type' => 'checkbox',
      '#title' => t('My setting'),
      '#default_value' => $context['formatter']->getSetting('mysetting'),
    );
  }
}

/**
 * Alters the widget settings form.
 *
 * @param array $element
 *   Form array.
 * @param array $form_state
 *   The form state of the (entire) configuration form.
 * @param array $context
 *   An associative array with the following elements:
 *   - formatter: The formatter object.
 *   - field_definition: The field definition.
 *   - form_mode: The form mode being configured.
 *   - form: The (entire) configuration form array.
 *
 * @see \Drupal\field_ui\FormDisplayOverView.
 */
function hook_field_widget_settings_form_alter(&$element, &$form_state, $context) {
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
 *   - field_definition: The field definition.
 *   - view_mode: The view mode being configured.
 *
 * @see \Drupal\field_ui\DisplayOverView.
 */
function hook_field_formatter_settings_summary_alter(&$summary, $context) {
  // Append a message to the summary when an instance of foo_formatter has
  // mysetting set to TRUE for the current view mode.
  if ($context['formatter']->getPluginId() == 'foo_formatter') {
    if ($context['formatter']->getSetting('mysetting')) {
      $summary[] = t('My setting enabled.');
    }
  }
}

/**
 * Alters the field widget settings summary.
 *
 * @param array $summary
 *   The summary.
 * @param array $context
 *   An associative array with the following elements:
 *   - widget: The widget object.
 *   - field_definition: The field definition.
 *   - form_mode: The form mode being configured.
 *
 * @see \Drupal\field_ui\FormDisplayOverView.
 */
function hook_field_widget_settings_summary_alter(&$summary, $context) {
  // Append a message to the summary when an instance of foo_field has
  // mysetting set to TRUE for the current view mode.
  if ($context['field']['type'] == 'foo_field') {
    if ($context['widget']->getSetting('mysetting')) {
      $summary[] = t('My setting enabled.');
    }
  }
}

/**
 * @} End of "addtogroup field_types".
 */
