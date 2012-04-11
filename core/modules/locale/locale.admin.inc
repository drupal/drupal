<?php

/**
 * @file
 * Administration functions for locale.module.
 */

/**
 * Returns HTML for a locale date format form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_locale_date_format_form($variables) {
  $form = $variables['form'];
  $header = array(
    t('Date type'),
    t('Format'),
  );

  foreach (element_children($form['date_formats']) as $key) {
    $row = array();
    $row[] = $form['date_formats'][$key]['#title'];
    unset($form['date_formats'][$key]['#title']);
    $row[] = array('data' => drupal_render($form['date_formats'][$key]));
    $rows[] = $row;
  }

  $output = drupal_render($form['language']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Display edit date format links for each language.
 */
function locale_date_format_language_overview_page() {
  $header = array(
    t('Language'),
    array('data' => t('Operations'), 'colspan' => '2'),
  );

  // Get the enabled languages only.
  $languages = language_list(TRUE);

  foreach ($languages as $langcode => $language) {
    $row = array();
    $row[] = $language->name;
    $row[] = l(t('edit'), 'admin/config/regional/date-time/locale/' . $langcode . '/edit');
    $row[] = l(t('reset'), 'admin/config/regional/date-time/locale/' . $langcode . '/reset');
    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Provide date localization configuration options to users.
 */
function locale_date_format_form($form, &$form_state, $langcode) {
  // Display the current language name.
  $form['language'] = array(
    '#type' => 'item',
    '#title' => t('Language'),
    '#markup' => language_load($langcode)->name,
    '#weight' => -10,
  );
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $langcode,
  );

  // Get list of date format types.
  $types = system_get_date_types();

  // Get list of available formats.
  $formats = system_get_date_formats();
  $choices = array();
  foreach ($formats as $type => $list) {
    foreach ($list as $f => $format) {
      $choices[$f] = format_date(REQUEST_TIME, 'custom', $f);
    }
  }
  reset($formats);

  // Get configured formats for each language.
  $locale_formats = system_date_format_locale($langcode);
  // Display a form field for each format type.
  foreach ($types as $type => $type_info) {
    if (!empty($locale_formats) && in_array($type, array_keys($locale_formats))) {
      $default = $locale_formats[$type];
    }
    else {
      $default = variable_get('date_format_' . $type, key($formats));
    }

    // Show date format select list.
    $form['date_formats']['date_format_' . $type] = array(
      '#type' => 'select',
      '#title' => check_plain($type_info['title']),
      '#attributes' => array('class' => array('date-format')),
      '#default_value' => (isset($choices[$default]) ? $default : 'custom'),
      '#options' => $choices,
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Submit handler for configuring localized date formats on the locale_date_format_form.
 */
function locale_date_format_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  // Get list of date format types.
  $types = system_get_date_types();
  foreach ($types as $type => $type_info) {
    $format = $form_state['values']['date_format_' . $type];
    if ($format == 'custom') {
      $format = $form_state['values']['date_format_' . $type . '_custom'];
    }
    locale_date_format_save($langcode, $type, $format);
  }
  drupal_set_message(t('Configuration saved.'));
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}

/**
 * Reset locale specific date formats to the global defaults.
 *
 * @param $langcode
 *   Language code, e.g. 'en'.
 */
function locale_date_format_reset_form($form, &$form_state, $langcode) {
  $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
  return confirm_form($form,
    t('Are you sure you want to reset the date formats for %language to the global defaults?', array('%language' => language_load($langcode)->name)),
    'admin/config/regional/date-time/locale',
    t('Resetting will remove all localized date formats for this language. This action cannot be undone.'),
    t('Reset'), t('Cancel'));
}

/**
 * Reset date formats for a specific language to global defaults.
 */
function locale_date_format_reset_form_submit($form, &$form_state) {
  db_delete('date_format_locale')
    ->condition('language', $form_state['values']['langcode'])
    ->execute();
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}
