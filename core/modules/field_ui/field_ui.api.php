<?php

/**
 * @file
 */

use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetInterface;

/**
 * @file
 * Hooks provided by the Field UI module.
 */

/**
 * @addtogroup field_types
 * @{
 */

/**
 * Allow modules to add settings to field formatters provided by other modules.
 *
 * @param \Drupal\Core\Field\FormatterInterface $plugin
 *   The instantiated field formatter plugin.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param string $view_mode
 *   The entity view mode.
 * @param array $form
 *   The (entire) configuration form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 *
 * @return array
 *   Returns the form array to be built.
 *
 * @see \Drupal\field_ui\Form\EntityViewDisplayEditForm::thirdPartySettingsForm()
 */
function hook_field_formatter_third_party_settings_form(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, array $form, FormStateInterface $form_state) {
  $element = [];
  // Add a 'my_setting' checkbox to the settings form for 'foo_formatter' field
  // formatters.
  if ($plugin->getPluginId() == 'foo_formatter') {
    $element['my_setting'] = [
      '#type' => 'checkbox',
      '#title' => t('My setting'),
      '#default_value' => $plugin->getThirdPartySetting('my_module', 'my_setting'),
    ];
  }
  return $element;
}

/**
 * Allow modules to add settings to field widgets provided by other modules.
 *
 * @param \Drupal\Core\Field\WidgetInterface $plugin
 *   The instantiated field widget plugin.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param string $form_mode
 *   The entity form mode.
 * @param array $form
 *   The (entire) configuration form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 *
 * @return array
 *   Returns the form array to be built.
 *
 * @see \Drupal\field_ui\Form\EntityFormDisplayEditForm::thirdPartySettingsForm()
 */
function hook_field_widget_third_party_settings_form(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, array $form, FormStateInterface $form_state) {
  $element = [];
  // Add a 'my_setting' checkbox to the settings form for 'foo_widget' field
  // widgets.
  if ($plugin->getPluginId() == 'foo_widget') {
    $element['my_setting'] = [
      '#type' => 'checkbox',
      '#title' => t('My setting'),
      '#default_value' => $plugin->getThirdPartySetting('my_module', 'my_setting'),
    ];
  }
  return $element;
}

/**
 * Alters the field formatter settings summary.
 *
 * @param array $summary
 *   An array of summary messages.
 * @param array $context
 *   An associative array with the following elements:
 *   - formatter: The formatter plugin.
 *   - field_definition: The field definition.
 *   - view_mode: The view mode being configured.
 *
 * @see \Drupal\field_ui\Form\EntityViewDisplayEditForm::alterSettingsSummary()
 */
function hook_field_formatter_settings_summary_alter(array &$summary, array $context) {
  // Append a message to the summary when an instance of foo_formatter has
  // my_setting set to TRUE for the current view mode.
  if ($context['formatter']->getPluginId() == 'foo_formatter') {
    if ($context['formatter']->getThirdPartySetting('my_module', 'my_setting')) {
      $summary[] = t('My setting enabled.');
    }
  }
}

/**
 * Alters the field widget settings summary.
 *
 * @param array $summary
 *   An array of summary messages.
 * @param array $context
 *   An associative array with the following elements:
 *   - widget: The widget object.
 *   - field_definition: The field definition.
 *   - form_mode: The form mode being configured.
 *
 * @see \Drupal\field_ui\Form\EntityFormDisplayEditForm::alterSettingsSummary()
 */
function hook_field_widget_settings_summary_alter(array &$summary, array $context) {
  // Append a message to the summary when an instance of foo_widget has
  // my_setting set to TRUE for the current view mode.
  if ($context['widget']->getPluginId() == 'foo_widget') {
    if ($context['widget']->getThirdPartySetting('my_module', 'my_setting')) {
      $summary[] = t('My setting enabled.');
    }
  }
}

/**
 * @} End of "addtogroup field_types".
 */
