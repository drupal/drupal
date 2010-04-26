<?php
// $Id: field_ui.api.php,v 1.5 2010/04/26 14:40:47 dries Exp $

/**
 * @file
 * Hooks provided by the Field UI module.
 */

/**
 * @ingroup field_ui_field_type
 * @{
 */

/**
 * Field settings form.
 *
 * The field type module is responsible for not returning form elements that
 * cannot be changed. It may not always be possible to tell in advance, but
 * modules should attempt to inform the user what is going/likely to work.
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
 *   Whether the field already has data.
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
 * Instance settings form.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
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
 * Widget settings form.
 *
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
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
 * Formatter settings form.
 *
 * @todo Not implemented yet. The signature below is only prospective, but
 * providing $instance is not enough, since one $instance holds several display
 * settings.
 *
 * @param $formatter
 *   The type of the formatter being configured.
 * @param $settings
 *   The current values of the formatter settings.
 * @param $field
 *   The field structure being configured.
 * @param $instance
 *   The instance structure being configured.
 * @return
 *   The form definition for the formatter settings.
 */
function hook_field_formatter_settings_form($formatter, $settings, $field, $instance) {
}

/**
 * Provide information on view mode tabs for an entity type.
 *
 * @param $entity_type
 *   The type of entity to return tabs for.
 *
 * @return
 *   An array whose keys are internal-use tab names, and whose values are
 *   arrays of tab information, with the following elements:
 *   - 'title': Human-readable title of the tab.
 *   - 'view modes': Array of view modes for this entity type that should
 *     be displayed on this tab.
 *
 * @see field_ui_view_modes_tabs()
 */
function hook_field_ui_view_modes_tabs($entity_type) {
  $modes = array(
    'basic' => array(
      'title' => t('Basic'),
      'view modes' => array('teaser', 'full'),
    ),
    'rss' => array(
      'title' => t('RSS'),
      'view modes' => array('rss'),
    ),
    'print' => array(
      'title' => t('Print'),
      'view modes' => array('print'),
    ),
    'search' => array(
      'title' => t('Search'),
      'view modes' => array('search_index', 'search_result'),
    ),
  );
  return $modes;
}

/**
 * @} End of "ingroup field_ui_field_type"
 */
