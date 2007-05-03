<?php
// $Id: locale.inc,v 1.119 2007/05/03 09:51:07 dries Exp $

/**
 * @file
 *   Administration functions for locale.module.
 */

/**
 * @defgroup locale-language-overview Language overview functionality
 * @{
 */

/**
 * User interface for the language overview screen.
 */
function locale_languages_overview_form() {
  $languages = language_list('language', TRUE);

  $options = array();
  $form['weight'] = array('#tree' => TRUE);
  foreach ($languages as $langcode => $language) {

    $options[$langcode] = '';
    if ($language->enabled) {
      $enabled[] = $langcode;
    }
    $form['weight'][$langcode] = array(
      '#type' => 'weight',
      '#default_value' => $language->weight
    );
    $form['name'][$langcode] = array('#value' => check_plain($language->name));
    $form['native'][$langcode] = array('#value' => check_plain($language->native));
    $form['direction'][$langcode] = array('#value' => ($language->direction == LANGUAGE_RTL ? 'Right to left' : 'Left to right'));
  }
  $form['enabled'] = array('#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $enabled,
  );
  $default = language_default();
  $form['site_default'] = array('#type' => 'radios',
    '#options' => $options,
    '#default_value' => $default->language,
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  $form['#theme'] = 'locale_languages_overview_form';

  return $form;
}

/**
 * Theme the langauge overview form.
 */
function theme_locale_languages_overview_form($form) {
  $default = language_default();
  foreach ($form['name'] as $key => $element) {
    // Do not take form control structures.
    if (is_array($element) && element_child($key)) {
      $rows[] = array(array('data' => drupal_render($form['enabled'][$key]), 'align' => 'center'), check_plain($key), '<strong>'. drupal_render($form['name'][$key]) .'</strong>', drupal_render($form['native'][$key]), drupal_render($form['direction'][$key]), drupal_render($form['site_default'][$key]), drupal_render($form['weight'][$key]), l(t('edit'), 'admin/settings/language/edit/'. $key). (($key != 'en' && $key != $default->language) ? ' '. l(t('delete') ,'admin/settings/language/delete/'. $key) : ''));
    }
  }
  $header = array(array('data' => t('Enabled')), array('data' => t('Code')), array('data' => t('English name')), array('data' => t('Native name')), array('data' => t('Direction')), array('data' => t('Default')), array('data' => t('Weight')), array('data' => t('Operations')));
  $output = theme('table', $header, $rows);
  $output .= drupal_render($form);

  return $output;
}

/**
 * Process language overview form submissions, updating existing languages.
 */
function locale_languages_overview_form_submit($form_id, $form_values) {
  $languages = language_list();
  $enabled_count = 0;
  foreach ($languages as $langcode => $language) {
    if ($form_values['site_default'] == $langcode) {
      // Automatically enable the default language.
      $form_values['enabled'][$langcode] = 1;
    }
    if ($form_values['enabled'][$langcode]) {
      $enabled_count++;
      $language->enabled = 1;
    }
    else {
      $language->enabled = 0;
    }
    $language->weight = $form_values['weight'][$langcode];
    db_query("UPDATE {languages} SET enabled = %d, weight = %d WHERE language = '%s'", $language->enabled, $language->weight, $langcode);
    $languages[$langcode] = $language;
  }
  drupal_set_message(t('Configuration saved.'));
  variable_set('language_default', $languages[$form_values['site_default']]);
  variable_set('language_count', $enabled_count);

  // Changing the language settings impacts the interface.
  cache_clear_all('*', 'cache_page', TRUE);

  return 'admin/settings/language';
}
/**
 * @} End of "locale-language-overview"
 */

/**
 * @defgroup locale-language-add-edit Language addition and editing functionality
 * @{
 */

/**
 * User interface for the language addition screen.
 */
function locale_languages_add_screen() {
  $output = drupal_get_form('locale_languages_predefined_form');
  $output .= drupal_get_form('locale_languages_custom_form');
  return $output;
}

/**
 * Predefined language setup form.
 */
function locale_languages_predefined_form() {
  $predefined = _locale_prepare_predefined_list();
  $form = array();
  $form['language list'] = array('#type' => 'fieldset',
    '#title' => t('Predefined language'),
    '#collapsible' => TRUE,
  );
  $form['language list']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#default_value' => key($predefined),
    '#options' => $predefined,
    '#description' => t('Select the desired language here, or add it below, if you are unable to find it in the list.'),
  );
  $form['language list']['submit'] = array('#type' => 'submit', '#value' => t('Add language'));
  return $form;
}

/**
 * Custom language addition form.
 */
function locale_languages_custom_form() {
  $form = array();
  $form['custom language'] = array('#type' => 'fieldset',
    '#title' => t('Custom language'),
    '#collapsible' => TRUE,
  );
  _locale_languages_common_controls($form['custom language']);
  $form['custom language']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add custom language')
  );
  // Reuse the validation and submit functions of the predefined language setup form.
  $form['#submit']['locale_languages_predefined_form_submit'] = array();
  $form['#validate']['locale_languages_predefined_form_validate'] = array();
  return $form;
}

/**
 * Editing screen for a particular language.
 *
 * @param $langcode
 *   Language code of the language to edit.
 */
function locale_languages_edit_form($langcode) {
  if ($language = db_fetch_object(db_query("SELECT * FROM {languages} WHERE language = '%s'", $langcode))) {
    $form = array();
    _locale_languages_common_controls($form, $language);
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save language')
    );
    $form['#submit']['locale_languages_edit_form_submit'] = array();
    $form['#validate']['locale_languages_edit_form_validate'] = array();
    return $form;
  }
  else {
    drupal_not_found();
  }
}

/**
 * Common elements of the language addition and editing form.
 *
 * @param $form
 *   A parent form item (or empty array) to add items below.
 * @param $language
 *   Language object to edit.
 */
function _locale_languages_common_controls(&$form, $language = NULL) {
  if (!is_object($language)) {
    $language = new stdClass();
  }
  if (isset($language->language)) {
    $form['langcode_view'] = array(
      '#type' => 'item',
      '#title' => t('Language code'),
      '#value' => $language->language
    );
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $language->language
    );
  }
  else {
    $form['langcode'] = array('#type' => 'textfield',
      '#title' => t('Language code'),
      '#size' => 12,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#default_value' => @$language->language,
      '#disabled' => (isset($language->language)),
      '#description' => t('This should be an <a href="@rfc4646">RFC 4646</a> compliant language identifier. Basic tags use a country code with an optional script or regional variant name, like "en", "en-US" and "zh-Hant".', array('@rfc4646' => 'http://www.ietf.org/rfc/rfc4646.txt')),
    );
  }
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Language name in English'),
    '#maxlength' => 64,
    '#default_value' => @$language->name,
    '#required' => TRUE,
    '#description' => t('Name of the language. Will be available for translation in all languages.'),
  );
  $form['native'] = array('#type' => 'textfield',
    '#title' => t('Native language name'),
    '#maxlength' => 64,
    '#default_value' => @$language->native,
    '#required' => TRUE,
    '#description' => t('Name of the language in the language being added.'),
  );
  $form['prefix'] = array('#type' => 'textfield',
    '#title' => t('Path prefix'),
    '#maxlength' => 64,
    '#default_value' => @$language->prefix,
    '#description' => t('Optional path prefix, for example "deutsch" for the German version. This value is not used in the "None" and "Domain" negotiation schemes. You can leave this empty if you use "Path only" negotiation and this is the default language. Changing this will break existing URLs.')
  );
  $form['domain'] = array('#type' => 'textfield',
    '#title' => t('Language domain'),
    '#maxlength' => 64,
    '#default_value' => @$language->domain,
    '#description' => t('Optional custom domain with protocol (eg. "http://example.de" or "http://de.example.com" for the German version). This value is only used in the "Domain" negotiation mode. If left empty and in "Domain" mode, this language will not be accessible.'),
  );
  $form['direction'] = array('#type' => 'radios',
    '#title' => t('Direction'),
    '#required' => TRUE,
    '#description' => t('Direction of the text being written in this language.'),
    '#default_value' => @$language->direction,
    '#options' => array(LANGUAGE_LTR => t('Left to right'), LANGUAGE_RTL => t('Right to left'))
  );
  return $form;
}

/**
 * Validate the language addition form.
 */
function locale_languages_predefined_form_validate($form_id, $form_values) {
  $langcode = $form_values['langcode'];

  if ($duplicate = db_num_rows(db_query("SELECT language FROM {languages} WHERE language = '%s'", $langcode)) != 0) {
    form_set_error('langcode', t('The language %language (%code) already exists.', array('%language' => $form_values['name'], '%code' => $langcode)));
  }

  if (!isset($form_values['name'])) {
    // Predefined language selection.
    $predefined = _locale_get_predefined_list();
    if (!isset($predefined[$langcode])) {
      form_set_error('langcode', t('Invalid language code.'));
    }
  }
  else {
    // Reuse the editing form validation routine if we add a custom language.
    locale_languages_edit_form_validate($form_id, $form_values);
  }
}

/**
 * Process the language addition form submission.
 */
function locale_languages_predefined_form_submit($form_id, $form_values) {
  $langcode = $form_values['langcode'];
  if (isset($form_values['name'])) {
    // Custom language form.
    locale_add_language($langcode, $form_values['name'], $form_values['native'], $form_values['direction'], $form_values['domain'], $form_values['prefix']);
  }
  else {
    // Predefined language selection.
    $predefined = _locale_get_predefined_list();
    $lang = &$predefined[$langcode];
    locale_add_language($langcode, $lang[0], isset($lang[1]) ? $lang[1] : $lang[0], isset($lang[2]) ? $lang[2] : 0, '', $langcode);
  }

  return 'admin/settings/language';
}

/**
 * Validate the language editing form. Reused for custom language addition too.
 */
function locale_languages_edit_form_validate($form_id, $form_values) {
  if (!empty($form_values['domain']) && !empty($form_values['prefix'])) {
    form_set_error('prefix', t('Domain and path prefix values should not be set at the same time.'));
  }
  if (!empty($form_values['domain']) && $duplicate = db_fetch_object(db_query("SELECT language FROM {languages} WHERE domain = '%s' AND language != '%s'", $form_values['domain'], $form_values['langcode']))) {
    form_set_error('domain', t('The domain (%domain) is already tied to a language (%language).', array('%domain' => $form_values['domain'], '%language' => $duplicate->language)));
  }
  $default = language_default();
  if (empty($form_values['prefix']) && $default->language != $form_values['langcode'] && empty($form_values['domain'])) {
    form_set_error('prefix', t('Only the default language can have both the domain and prefix empty.'));
  }
  if (!empty($form_values['prefix']) && $duplicate = db_fetch_object(db_query("SELECT language FROM {languages} WHERE prefix = '%s' AND language != '%s'", $form_values['prefix'], $form_values['langcode']))) {
    form_set_error('prefix', t('The prefix (%prefix) is already tied to a language (%language).', array('%prefix' => $form_values['prefix'], '%language' => $duplicate->language)));
  }
}

/**
 * Process the language editing form submission.
 */
function locale_languages_edit_form_submit($form_id, $form_values) {
  db_query("UPDATE {languages} SET name = '%s', native = '%s', domain = '%s', prefix = '%s', direction = %d WHERE language = '%s'", $form_values['name'], $form_values['native'], $form_values['domain'], $form_values['prefix'], $form_values['direction'], $form_values['langcode']);
  $default = language_default();
  if ($default->language == $form_values['langcode']) {
    $properties = array('name', 'native', 'direction', 'enabled', 'plurals', 'formula', 'domain', 'prefix', 'weight');
    foreach ($properties as $keyname) {
      $default->$keyname = $form_values[$keyname];
    }
    variable_set('language_default', $default);
  }
  return 'admin/settings/language';
}
/**
 * @} End of "locale-language-add-edit"
 */

/**
 * @defgroup locale-language-delete Language deletion functionality
 * @{
 */

/**
 * User interface for the language deletion confirmation screen.
 */
function locale_languages_delete_form($langcode) {

  // Do not allow deletion of English locale.
  if ($langcode == 'en') {
    drupal_set_message(t('The English language cannot be deleted.'));
    drupal_goto('admin/settings/language');
  }

  $default = language_default();
  if ($default->language == $langcode) {
    drupal_set_message(t('The default language cannot be deleted.'));
    drupal_goto('admin/settings/language');
  }

  // For other languages, warn user that data loss is ahead.
  $languages = language_list();

  if (!isset($languages[$langcode])) {
    drupal_not_found();
  }
  else {
    $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
    return confirm_form($form, t('Are you sure you want to delete the language %name?', array('%name' => t($languages[$langcode]->name))), 'admin/settings/language', t('Deleting a language will remove all interface translations associated with it, and posts in this language will be set to be language neutral. This action cannot be undone.'), t('Delete'), t('Cancel'));
  }
}

/**
 * Process language deletion submissions.
 */
function locale_languages_delete_form_submit($form_id, $form_values) {
  $languages = language_list();
  if (isset($languages[$form_values['langcode']])) {
    db_query("DELETE FROM {languages} WHERE language = '%s'", $form_values['langcode']);
    db_query("DELETE FROM {locales_target} WHERE language = '%s'", $form_values['langcode']);
    db_query("UPDATE {node} SET language = '' WHERE language = '%s'", $form_values['langcode']);
    $variables = array('%locale' => $languages[$form_values['langcode']]->name);
    drupal_set_message(t('The language %locale has been removed.', $variables));
    watchdog('locale', 'The language %locale has been removed.', $variables);
  }

  // Changing the language settings impacts the interface:
  cache_clear_all('*', 'cache_page', TRUE);

  return 'admin/settings/language';
}
/**
 * @} End of "locale-language-add-edit"
 */

/**
 * @defgroup locale-languages-negotiation Language negotiation options screen
 * @{
 */

/**
 * Setting for language negotiation options
 */
function locale_languages_configure_form() {
  $form['language_negotiation'] = array(
    '#title' => t('Language negotiation'),
    '#type' => 'radios',
    '#options' => array(
      LANGUAGE_NEGOTIATION_NONE => t('None. Language will be independent of visitor preferences and language prefixes or domains.'),
      LANGUAGE_NEGOTIATION_PATH_DEFAULT => t('Path prefix only. If a suitable path prefix is not identified, the default language is used.'),
      LANGUAGE_NEGOTIATION_PATH => t('Path prefix with language fallback. If a suitable  path prefix is not identified, language is based on user preferences and browser language settings.'),
      LANGUAGE_NEGOTIATION_DOMAIN => t('Domain name only. If a suitable domain name is not identified, the default language is used.')),
    '#default_value' => variable_get('language_negotiation', LANGUAGE_NEGOTIATION_NONE),
    '#description' => t('The used language detection mode. Changing this also changes how paths are constructed, so setting a different value breaks all incoming links. Do not change on a live site without thinking twice!')
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings')
  );
  return $form;
}

/**
 * Submit function for language negotiation settings.
 */
function locale_languages_configure_form_submit($form_id, $form_values) {
  variable_set('language_negotiation', $form_values['language_negotiation']);
  drupal_set_message(t('Language negotiation configuration saved.'));
  return 'admin/settings/language';
}
/**
 * @} End of "locale-languages-negotiation"
 */

/**
 * @defgroup locale-translate-overview Translation overview screen.
 * @{
 */

/**
 * Overview screen for translations.
 */
function locale_translate_overview_screen() {
  $languages = language_list('language', TRUE);
  $groups = module_invoke_all('locale', 'groups');

  // Build headers with all groups in order.
  $headers = array_merge(array(t('Language')), array_values($groups));

  // Collect summaries of all source strings in all groups.
  $sums = db_query("SELECT COUNT(*) AS strings, textgroup FROM {locales_source} GROUP BY textgroup");
  $groupsums = array();
  while ($group = db_fetch_object($sums)) {
    $groupsums[$group->textgroup] = $group->strings;
  }

  // Setup overview table with default values, ensuring common order for values.
  $rows = array();
  foreach ($languages as $langcode => $language) {
    $rows[$langcode] = array('name' => ($langcode == 'en' ? t('English (built-in)') : t($language->name)));
    foreach ($groups as $group => $name) {
      $rows[$langcode][$group] = ($langcode == 'en' ? t('n/a') : '0/'. (isset($groupsums[$group]) ? $groupsums[$group] : 0) . ' (0%)');
    }
  }

  // Languages with at least one record in the locale table.
  $translations = db_query("SELECT COUNT(*) AS translation, t.language, s.textgroup FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid WHERE translation != '' GROUP BY textgroup, language");
  while ($data = db_fetch_object($translations)) {
    $ratio = (!empty($groupsums[$data->textgroup]) && $data->translation > 0) ? round(($data->translation/$groupsums[$data->textgroup])*100., 2) : 0;
    $rows[$data->language][$data->textgroup] = $data->translation .'/'. $groupsums[$data->textgroup] ." ($ratio%)";
  }

  return theme('table', $headers, $rows);
}
/**
 * @} End of "locale-translate-overview"
 */

/**
 * @defgroup locale-translate-seek Translation search screen.
 * @{
 */

/**
 * String search screen.
 */
function locale_translate_seek_screen() {
  $output = _locale_translate_seek();
  $output .= drupal_get_form('locale_translate_seek_form');
  return $output;
}

/**
 * User interface for the string search screen.
 */
function locale_translate_seek_form() {
  // Get all languages, except English
  $languages = locale_language_list('name', TRUE);
  unset($languages['en']);

  // Present edit form preserving previous user settings
  $query = _locale_translate_seek_query();
  $form = array();
  $form['search'] = array('#type' => 'fieldset',
    '#title' => t('Search'),
  );
  $form['search']['string'] = array('#type' => 'textfield',
    '#title' => t('String contains'),
    '#default_value' => @$query['string'],
    '#description' => t('Leave blank to show all strings. The search is case sensitive.'),
  );
  $form['search']['language'] = array('#type' => 'radios',
    '#title' => t('Language'),
    '#default_value' => (!empty($query['language']) ? $query['language'] : 'all'),
    '#options' => array_merge(array('all' => t('All languages'), 'en' => t('English (provided by Drupal)')), $languages),
  );
  $form['search']['translation'] = array('#type' => 'radios',
    '#title' => t('Search in'),
    '#default_value' => (!empty($query['translation']) ? $query['translation'] : 'all'),
    '#options' => array('all' => t('All strings in that language'), 'translated' => t('Only translated strings'), 'untranslated' => t('Only untranslated strings')),
  );
  $groups = module_invoke_all('locale', 'groups');
  $form['search']['group'] = array('#type' => 'radios',
    '#title' => t('Limit search to'),
    '#default_value' => (!empty($query['group']) ? $query['group'] : 'all'),
    '#options' => array_merge(array('all' => t('All text groups')), $groups),
  );

  $form['search']['submit'] = array('#type' => 'submit', '#value' => t('Search'));
  $form['#redirect'] = FALSE;

  return $form;
}
/**
 * @} End of "locale-translate-seek"
 */

/**
 * @defgroup locale-translate-import Translation import screen.
 * @{
 */

/**
 * User interface for the translation import screen.
 */
function locale_translate_import_form() {
  // Get all languages, except English
  $names = locale_language_list('name', TRUE);
  unset($names['en']);

  if (!count($names)) {
    $languages = _locale_prepare_predefined_list();
    $default = array_shift(array_keys($languages));
  }
  else {
    $languages = array(
      t('Already added languages') => $names,
      t('Languages not yet added') => _locale_prepare_predefined_list()
    );
    $default = array_shift(array_keys($names));
  }

  $form = array();
  $form['import'] = array('#type' => 'fieldset',
    '#title' => t('Import translation'),
  );
  $form['import']['file'] = array('#type' => 'file',
    '#title' => t('Language file'),
    '#size' => 50,
    '#description' => t('A gettext Portable Object (.po) file.'),
  );
  $form['import']['langcode'] = array('#type' => 'select',
    '#title' => t('Import into'),
    '#options' => $languages,
    '#default_value' => $default,
    '#description' => t('Choose the language you want to add strings into. If you choose a language which is not yet set up, it will be added.'),
  );
  $form['import']['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
    '#description' => t('Imported translations will be added to this text group.'),
  );
  $form['import']['mode'] = array('#type' => 'radios',
    '#title' => t('Mode'),
    '#default_value' => 'overwrite',
    '#options' => array('overwrite' => t('Strings in the uploaded file replace existing ones, new ones are added'), 'keep' => t('Existing strings are kept, only new strings are added')),
  );
  $form['import']['submit'] = array('#type' => 'submit', '#value' => t('Import'));
  $form['#attributes']['enctype'] = 'multipart/form-data';

  return $form;
}

/**
 * Process the locale import form submission.
 */
function locale_translate_import_form_submit($form_id, $form_values) {
  // Ensure we have the file uploaded
  if ($file = file_check_upload('file')) {

    // Add language, if not yet supported
    $languages = language_list('language', TRUE);
    $langcode = $form_values['langcode'];
    if (!isset($languages[$langcode])) {
      $predefined = _locale_get_predefined_list();
      $lang = &$predefined[$langcode];
      locale_add_language($langcode, $lang[0], isset($lang[1]) ? $lang[1] : '', isset($lang[2]) ? $lang[2] : 0, '', '', FALSE);
    }

    // Now import strings into the language
    if ($ret = _locale_import_po($file, $langcode, $form_values['mode'], $form_values['group']) == FALSE) {
      $variables = array('%filename' => $file->filename);
      drupal_set_message(t('The translation import of %filename failed.', $variables), 'error');
      watchdog('locale', 'The translation import of %filename failed.', $variables, WATCHDOG_ERROR);
    }
  }
  else {
    drupal_set_message(t('File to import not found.'), 'error');
    return 'admin/build/translate/import';
  }

  return 'admin/build/translate';
}
/**
 * @} End of "locale-translate-import"
 */

/**
 * @defgroup locale-translate-export Translation export screen.
 * @{
 */

/**
 * User interface for the translation export screen.
 */
function locale_translate_export_screen() {
  // Get all languages, except English
  $names = locale_language_list('name', TRUE);
  unset($names['en']);
  $output = '';
  // Offer translation export if any language is set up.
  if (count($names)) {
    $output = drupal_get_form('locale_translate_export_po_form', $names);
  }
  $output .= drupal_get_form('locale_translate_export_pot_form');
  return $output;
}

/**
 * Form to export PO files for the languages provided.
 *
 * @param $names
 *   An associate array with localized language names
 */
function locale_translate_export_po_form($names) {
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export translation'),
    '#collapsible' => TRUE,
  );
  $form['export']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $names,
    '#description' => t('Select the language you would like to export in gettext Portable Object (.po) format.'),
  );
  $form['export']['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  return $form;
}

/**
 * Translation template export form.
 */
function locale_translate_export_pot_form() {
  // Complete template export of the strings
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export template'),
    '#collapsible' => TRUE,
    '#description' => t('Generate a gettext Portable Object Template (.pot) file with all strings from the Drupal locale database.'),
  );
  $form['export']['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  // Reuse PO export submission callback.
  $form['#submit']['locale_translate_export_po_form_submit'] = array();
  $form['#validate']['locale_translate_export_po_form_validate'] = array();
  return $form;
}

/**
 * Process a translation (or template) export form submission.
 */
function locale_translate_export_po_form_submit($form_id, $form_values) {
  // If template is required, language code is not given.
  _locale_export_po(isset($form_values['langcode']) ? $form_values['langcode'] : NULL, $form_values['group']);
}
/**
 * @} End of "locale-translate-export"
 */

/**
 * @defgroup locale-translate-edit Translation text editing
 * @{
 */

/**
 * User interface for string editing.
 */
function locale_translate_edit_form($lid) {
  $languages = language_list();
  unset($languages['en']);

  $result = db_query('SELECT DISTINCT s.source, t.translation, t.language FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE s.lid = %d', $lid);
  $form = array();
  $form['translations'] = array('#tree' => TRUE);
  while ($translation = db_fetch_object($result)) {
    $orig = $translation->source;

    // Approximate the number of rows in a textfield with a maximum of 10.
    $rows = min(ceil(str_word_count($orig) / 12), 10);

    $form['translations'][$translation->language] = array(
      '#type' => 'textarea',
      '#title' => $languages[$translation->language]->name,
      '#default_value' => $translation->translation,
      '#rows' => $rows,
    );
    unset($languages[$translation->language]);
  }

  // Handle erroneous lid.
  if (!isset($orig)) {
    drupal_set_message(t('String not found.'), 'error');
    drupal_goto('admin/build/translate/search');
  }

  // Add original text. Assign negative weight so that it floats to the top.
  $form['item'] = array('#type' => 'item',
    '#title' => t('Original text'),
    '#value' => check_plain(wordwrap($orig, 0)),
    '#weight' => -1,
  );

  foreach ($languages as $langcode => $language) {
    $form['translations'][$langcode] = array(
      '#type' => 'textarea',
      '#title' => t($language->name),
      '#rows' => $rows,
    );
  }

  $form['lid'] = array('#type' => 'value', '#value' => $lid);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save translations'));

  return $form;
}

/**
 * Process string editing form submissions.
 * Saves all translations of one string submitted from a form.
 */
function locale_translate_edit_form_submit($form_id, $form_values) {
  $lid = $form_values['lid'];
  foreach ($form_values['translations'] as $key => $value) {
    $trans = db_fetch_object(db_query("SELECT translation FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $key));
    if (isset($trans->translation)) {
      db_query("UPDATE {locales_target} SET translation = '%s' WHERE lid = %d AND language = '%s'", $value, $lid, $key);
    }
    else {
      db_query("INSERT INTO {locales_target} (lid, translation, language) VALUES (%d, '%s', '%s')", $lid, $value, $key);
    }
  }
  drupal_set_message(t('The string has been saved.'));

  // Refresh the locale cache.
  locale_refresh_cache();
  // Rebuild the menu, strings may have changed.
  menu_rebuild();

  return 'admin/build/translate/search';
}
/**
 * @} End of "locale-translate-edit"
 */

/**
 * @defgroup locale-translate-delete Translation delete interface.
 * @{
 */

/**
 * Delete a language string.
 */
function locale_translate_delete($lid) {
  db_query('DELETE FROM {locales_source} WHERE lid = %d', $lid);
  db_query('DELETE FROM {locales_target} WHERE lid = %d', $lid);
  locale_refresh_cache();
  drupal_set_message(t('The string has been removed.'));
  drupal_goto('admin/build/translate/search');
}
/**
 * @} End of "locale-translate-delete"
 */

/**
 * @defgroup locale-api-add Language addition API.
 * @{
 */

/**
 * API function to add a language.
 *
 * @param $langcode
 *   Language code.
 * @param $name
 *   English name of the language
 * @param $native
 *   Native name of the language
 * @param $direction
 *   LANGUAGE_LTR or LANGUAGE_RTL
 * @param $domain
 *   Optional custom domain name with protocol, without
 *   trailing slash (eg. http://de.example.com).
 * @param $prefix
 *   Optional path prefix for the language. Defaults to the
 *   language code if omitted.
 * @param $verbose
 *   Switch to omit the verbose message to the user when used
 *   only as a utility function.
 */
function locale_add_language($langcode, $name, $native, $direction = LANGUAGE_RTL, $domain = '', $prefix = '', $verbose = TRUE) {
  // Default prefix on language code.
  if (empty($prefix)) {
    $prefix = $code;
  }

  db_query("INSERT INTO {languages} (language, name, native, direction, domain, prefix) VALUES ('%s', '%s', '%s', %d, '%s', '%s')", $langcode, $name, $native, $direction, $domain, $prefix);

  // Add empty translations for strings (to optimize locale())
  $result = db_query("SELECT lid FROM {locales_source}");
  while ($string = db_fetch_object($result)) {
    db_query("INSERT INTO {locales_target} (lid, language, translation) VALUES (%d,'%s', '')", $string->lid, $code);
  }

  // Set message depending on the verbosity required.
  if ($verbose) {
    drupal_set_message(t('The language %language has been created and can now be used. More information is available on the <a href="@locale-help">help screen</a>.', array('%language' => t($name), '@locale-help' => url('admin/help/locale'))));
  }
  else {
    drupal_set_message(t('The language %language has been created.', array('%language' => t($name))));
  }

  watchdog('locale', 'The %language language (%code) has been created.', array('%language' => $name, '%code' => $langcode));
}
/**
 * @} End of "locale-api-add"
 */

/**
 * @defgroup locale-api-import Translation import API.
 * @{
 */

/**
 * Parses Gettext Portable Object file information and inserts into database
 *
 * @param $file
 *   Drupal file object corresponding to the PO file to import
 * @param $lang
 *   Language code
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_po($file, $lang, $mode, $group = NULL) {
  // If not in 'safe mode', increase the maximum execution time:
  if (!ini_get('safe_mode')) {
    set_time_limit(240);
  }

  // Check if we have the language already in the database
  if (!db_fetch_object(db_query("SELECT language FROM {languages} WHERE language = '%s'", $lang))) {
    drupal_set_message(t('The language selected for import is not supported.'), 'error');
    return FALSE;
  }

  // Get strings from file (returns on failure after a partial import, or on success)
  $status = _locale_import_read_po('db-store', $file, $mode, $lang, $group);
  if ($status === FALSE) {
    // error messages are set in _locale_import_read_po
    return FALSE;
  }

  // Get status information on import process
  list($headerdone, $additions, $updates) = _locale_import_one_string('db-report');

  if (!$headerdone) {
    drupal_set_message(t('The translation file %filename appears to have a missing or malformed header.', array('%filename' => $file->filename)), 'error');
  }

  // rebuild locale cache
  cache_clear_all("locale:$lang", 'cache');

  // rebuild the menu, strings may have changed
  menu_rebuild();

  drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings and %update strings were updated.', array('%number' => $additions, '%update' => $updates)));
  watchdog('locale', 'Imported %file into %locale: %number new strings added and %update updated.', array('%file' => $file->filename, '%locale' => $lang, '%number' => $additions, '%update' => $updates));
  return TRUE;
}

/**
 * Parses Gettext Portable Object file into an array
 *
 * @param $op
 *   Storage operation type: db-store or mem-store
 * @param $file
 *   Drupal file object corresponding to the PO file to import
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 * @param $lang
 *   Language code
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_read_po($op, $file, $mode = NULL, $lang = NULL, $group = NULL) {

  $fd = fopen($file->filepath, "rb"); // File will get closed by PHP on return
  if (!$fd) {
    _locale_import_message('The translation import failed, because the file %filename could not be read.', $file);
    return FALSE;
  }

  $context = "COMMENT"; // Parser context: COMMENT, MSGID, MSGID_PLURAL, MSGSTR and MSGSTR_ARR
  $current = array();   // Current entry being read
  $plural = 0;          // Current plural form
  $lineno = 0;          // Current line

  while (!feof($fd)) {
    $line = fgets($fd, 10*1024); // A line should not be this long
    $lineno++;
    $line = trim(strtr($line, array("\\\n" => "")));

    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file);
        $current = array();
        $current["#"][] = substr($line, 1);
        $context = "COMMENT";
      }
      else { // Parse error
        _locale_import_message('The translation file %filename contains an error: "msgstr" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
    }
    elseif (!strncmp("msgid_plural", $line, 12)) {
      if ($context != "MSGID") { // Must be plural form for current entry
        _locale_import_message('The translation file %filename contains an error: "msgid_plural" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 12));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgid"] = $current["msgid"] ."\0". $quoted;
      $context = "MSGID_PLURAL";
    }
    elseif (!strncmp("msgid", $line, 5)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file);
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        _locale_import_message('The translation file %filename contains an error: "msgid" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file,  $lineno);
        return FALSE;
      }
      $current["msgid"] = $quoted;
      $context = "MSGID";
    }
    elseif (!strncmp("msgstr[", $line, 7)) {
      if (($context != "MSGID") && ($context != "MSGID_PLURAL") && ($context != "MSGSTR_ARR")) { // Must come after msgid, msgid_plural, or msgstr[]
        _locale_import_message('The translation file %filename contains an error: "msgstr[]" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      if (strpos($line, "]") === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $frombracket = strstr($line, "[");
      $plural = substr($frombracket, 1, strpos($frombracket, "]") - 1);
      $line = trim(strstr($line, " "));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"][$plural] = $quoted;
      $context = "MSGSTR_ARR";
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      if ($context != "MSGID") {   // Should come just after a msgid block
        _locale_import_message('The translation file %filename contains an error: "msgstr" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 6));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"] = $quoted;
      $context = "MSGSTR";
    }
    elseif ($line != "") {
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      if (($context == "MSGID") || ($context == "MSGID_PLURAL")) {
        $current["msgid"] .= $quoted;
      }
      elseif ($context == "MSGSTR") {
        $current["msgstr"] .= $quoted;
      }
      elseif ($context == "MSGSTR_ARR") {
        $current["msgstr"][$plural] .= $quoted;
      }
      else {
        _locale_import_message('The translation file %filename contains an error: there is an unexpected string on line %line.', $file, $lineno);
        return FALSE;
      }
    }
  }

  // End of PO file, flush last entry
  if (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) {
    _locale_import_one_string($op, $current, $mode, $lang, $file);
  }
  elseif ($context != "COMMENT") {
    _locale_import_message('The translation file %filename ended unexpectedly at line %line.', $file, $lineno);
    return FALSE;
  }

}

/**
 * Sets an error message occurred during locale file parsing.
 *
 * @param $message
 *   The message to be translated
 * @param $file
 *   Drupal file object corresponding to the PO file to import
 * @param $lineno
 *   An optional line number argument
 */
function _locale_import_message($message, $file, $lineno = NULL) {
  $vars = array('%filename' => $file->filename);
  if (isset($lineno)) {
    $vars['%line'] = $lineno;
  }
  $t = get_t();
  drupal_set_message($t($message, $vars), 'error');
}

/**
 * Imports a string into the database
 *
 * @param $op
 *   Operation to perform: 'db-store', 'db-report', 'mem-store' or 'mem-report'
 * @param $value
 *   Details of the string stored
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 * @param $lang
 *   Language to store the string in
 * @param $file
 *   Object representation of file being imported, only required when op is 'db-store'
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_one_string($op, $value = NULL, $mode = NULL, $lang = NULL, $file = NULL, $group = 'default') {
  static $additions = 0;
  static $updates = 0;
  static $headerdone = FALSE;
  static $strings = array();

  switch ($op) {
    // Return stored strings
    case 'mem-report':
      return $strings;

    // Store string in memory (only supports single strings)
    case 'mem-store':
      $strings[$value['msgid']] = $value['msgstr'];
      return;

    // Called at end of import to inform the user
    case 'db-report':
      return array($headerdone, $additions, $updates);

    // Store the string we got in the database
    case 'db-store':
      // We got header information
      if ($value['msgid'] == '') {
        $hdr = _locale_import_parse_header($value['msgstr']);

        // Get the plural formula
        if (isset($hdr["Plural-Forms"]) && $p = _locale_import_parse_plural_forms($hdr["Plural-Forms"], $file->filename)) {
          list($nplurals, $plural) = $p;
          db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", $nplurals, $plural, $lang);
        }
        else {
          db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", 0, '', $lang);
        }
        $headerdone = TRUE;
      }

      // Some real string to import
      else {
        $comments = _locale_import_shorten_comments($value['#']);

        // Handle a translation for some plural string
        if (strpos($value['msgid'], "\0")) {
          $english = explode("\0", $value['msgid'], 2);
          $entries = array_keys($value['msgstr']);
          for ($i = 3; $i <= count($entries); $i++) {
            $english[] = $english[1];
          }
          $translation = array_map('_locale_import_append_plural', $value['msgstr'], $entries);
          $english = array_map('_locale_import_append_plural', $english, $entries);
          foreach ($translation as $key => $trans) {
            if ($key == 0) {
              $plid = 0;
            }
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english[$key], $group));
            if (!empty($loc->lid)) { // a string exists
              $lid = $loc->lid;
              // update location field
              db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $comments, $lid);
              $trans2 = db_fetch_object(db_query("SELECT lid, translation, plid, plural FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $lang));
              if (!isset($trans2->lid)) { // no translation in current language
                db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
                $additions++;
              } // translation exists
              else if ($mode == 'overwrite' || $trans2->translation == '') {
                db_query("UPDATE {locales_target} SET translation = '%s', plid = %d, plural = %d WHERE language = '%s' AND lid = %d", $trans, $plid, $key, $lang, $lid);
                if ($trans2->translation == '') {
                  $additions++;
                }
                else {
                  $updates++;
                }
              }
            }
            else { // no string
              db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', '%s')", $comments, $english[$key], $group);
              $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english[$key], $group));
              $lid = $loc->lid;
              db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
              if ($trans != '') {
                $additions++;
              }
            }
            $plid = $lid;
          }
        }

        // A simple translation
        else {
          $english = $value['msgid'];
          $translation = $value['msgstr'];
          $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english, $group));
          if (!empty($loc->lid)) { // a string exists
            $lid = $loc->lid;
            // update location field
            db_query("UPDATE {locales_source} SET location = '%s' WHERE source = '%s'", $comments, $english);
            $trans = db_fetch_object(db_query("SELECT lid, translation FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $lang));
            if (!isset($trans->lid)) { // no translation in current language
              db_query("INSERT INTO {locales_target} (lid, language, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
              $additions++;
            } // translation exists
            else if ($mode == 'overwrite') { //overwrite in any case
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE language = '%s' AND lid = %d", $translation, $lang, $lid);
              if ($trans->translation == '') {
                $additions++;
              }
              else {
                $updates++;
              }
            } // overwrite if empty string
            else if ($trans->translation == '') {
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE language = '%s' AND lid = %d", $translation, $lang, $lid);
              $additions++;
            }
          }
          else { // no string
            db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', '%s')", $comments, $english, $group);
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $english, $group));
            $lid = $loc->lid;
            db_query("INSERT INTO {locales_target} (lid, language, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
            if ($translation != '') {
              $additions++;
            }
          }
        }
      }
  } // end of db-store operation
}

/**
 * Parses a Gettext Portable Object file header
 *
 * @param $header A string containing the complete header
 * @return An associative array of key-value pairs
 */
function _locale_import_parse_header($header) {
  $hdr = array();

  $lines = explode("\n", $header);
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line) {
      list($tag, $contents) = explode(":", $line, 2);
      $hdr[trim($tag)] = trim($contents);
    }
  }

  return $hdr;
}

/**
 * Parses a Plural-Forms entry from a Gettext Portable Object file header
 *
 * @param $pluralforms
 *   A string containing the Plural-Forms entry
 * @param $filename
 *   A string containing the filename
 * @return
 *   An array containing the number of plurals and a
 *   formula in PHP for computing the plural form
 */
function _locale_import_parse_plural_forms($pluralforms, $filename) {
  // First, delete all whitespace
  $pluralforms = strtr($pluralforms, array(" " => "", "\t" => ""));

  // Select the parts that define nplurals and plural
  $nplurals = strstr($pluralforms, "nplurals=");
  if (strpos($nplurals, ";")) {
    $nplurals = substr($nplurals, 9, strpos($nplurals, ";") - 9);
  }
  else {
    return FALSE;
  }
  $plural = strstr($pluralforms, "plural=");
  if (strpos($plural, ";")) {
    $plural = substr($plural, 7, strpos($plural, ";") - 7);
  }
  else {
    return FALSE;
  }

  // Get PHP version of the plural formula
  $plural = _locale_import_parse_arithmetic($plural);

  if ($plural !== FALSE) {
    return array($nplurals, $plural);
  }
  else {
    drupal_set_message(t('The translation file %filename contains an error: the plural formula could not be parsed.', array('%filename' => $filename)), 'error');
    return FALSE;
  }
}

/**
 * Parses and sanitizes an arithmetic formula into a PHP expression
 *
 * While parsing, we ensure, that the operators have the right
 * precedence and associativity.
 *
 * @param $string A string containing the arithmetic formula
 * @return The PHP version of the formula
 */
function _locale_import_parse_arithmetic($string) {
  // Operator precedence table
  $prec = array("(" => -1, ")" => -1, "?" => 1, ":" => 1, "||" => 3, "&&" => 4, "==" => 5, "!=" => 5, "<" => 6, ">" => 6, "<=" => 6, ">=" => 6, "+" => 7, "-" => 7, "*" => 8, "/" => 8, "%" => 8);
  // Right associativity
  $rasc = array("?" => 1, ":" => 1);

  $tokens = _locale_import_tokenize_formula($string);

  // Parse by converting into infix notation then back into postfix
  $opstk = array();
  $elstk = array();

  foreach ($tokens as $token) {
    $ctok = $token;

    // Numbers and the $n variable are simply pushed into $elarr
    if (is_numeric($token)) {
      $elstk[] = $ctok;
    }
    elseif ($ctok == "n") {
      $elstk[] = '$n';
    }
    elseif ($ctok == "(") {
      $opstk[] = $ctok;
    }
    elseif ($ctok == ")") {
      $topop = array_pop($opstk);
      while (isset($topop) && ($topop != "(")) {
        $elstk[] = $topop;
        $topop = array_pop($opstk);
      }
    }
    elseif (!empty($prec[$ctok])) {
      // If it's an operator, then pop from $oparr into $elarr until the
      // precedence in $oparr is less than current, then push into $oparr
      $topop = array_pop($opstk);
      while (isset($topop) && ($prec[$topop] >= $prec[$ctok]) && !(($prec[$topop] == $prec[$ctok]) && !empty($rasc[$topop]) && !empty($rasc[$ctok]))) {
        $elstk[] = $topop;
        $topop = array_pop($opstk);
      }
      if ($topop) {
        $opstk[] = $topop;   // Return element to top
      }
      $opstk[] = $ctok;      // Parentheses are not needed
    }
    else {
      return FALSE;
    }
  }

  // Flush operator stack
  $topop = array_pop($opstk);
  while ($topop != NULL) {
    $elstk[] = $topop;
    $topop = array_pop($opstk);
  }

  // Now extract formula from stack
  $prevsize = count($elstk) + 1;
  while (count($elstk) < $prevsize) {
    $prevsize = count($elstk);
    for ($i = 2; $i < count($elstk); $i++) {
      $op = $elstk[$i];
      if (!empty($prec[$op])) {
        $f = "";
        if ($op == ":") {
          $f = $elstk[$i - 2] ."):". $elstk[$i - 1] .")";
        }
        elseif ($op == "?") {
          $f = "(". $elstk[$i - 2] ."?(". $elstk[$i - 1];
        }
        else {
          $f = "(". $elstk[$i - 2] . $op . $elstk[$i - 1] .")";
        }
        array_splice($elstk, $i - 2, 3, $f);
        break;
      }
    }
  }

  // If only one element is left, the number of operators is appropriate
  if (count($elstk) == 1) {
    return $elstk[0];
  }
  else {
    return FALSE;
  }
}

/**
 * Backward compatible implementation of token_get_all() for formula parsing
 *
 * @param $string A string containing the arithmetic formula
 * @return The PHP version of the formula
 */
function _locale_import_tokenize_formula($formula) {
  $formula = str_replace(" ", "", $formula);
  $tokens = array();
  for ($i = 0; $i < strlen($formula); $i++) {
    if (is_numeric($formula[$i])) {
      $num = $formula[$i];
      $j = $i + 1;
      while ($j < strlen($formula) && is_numeric($formula[$j])) {
        $num .= $formula[$j];
        $j++;
      }
      $i = $j - 1;
      $tokens[] = $num;
    }
    elseif ($pos = strpos(" =<>!&|", $formula[$i])) { // We won't have a space
      $next = $formula[$i + 1];
      switch ($pos) {
        case 1:
        case 2:
        case 3:
        case 4:
          if ($next == '=') {
            $tokens[] = $formula[$i] .'=';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 5:
          if ($next == '&') {
            $tokens[] = '&&';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 6:
          if ($next == '|') {
            $tokens[] = '||';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
      }
    }
    else {
      $tokens[] = $formula[$i];
    }
  }
  return $tokens;
}

/**
 * Modify a string to contain proper count indices
 *
 * This is a callback function used via array_map()
 *
 * @param $entry An array element
 * @param $key Index of the array element
 */
function _locale_import_append_plural($entry, $key) {
  // No modifications for 0, 1
  if ($key == 0 || $key == 1) {
    return $entry;
  }

  // First remove any possibly false indices, then add new ones
  $entry = preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
  return preg_replace('/(@count)/', "\\1[$key]", $entry);
}

/**
 * Generate a short, one string version of the passed comment array
 *
 * @param $comment An array of strings containing a comment
 * @return Short one string version of the comment
 */
function _locale_import_shorten_comments($comment) {
  $comm = '';
  while (count($comment)) {
    $test = $comm . substr(array_shift($comment), 1) .', ';
    if (strlen($comm) < 130) {
      $comm = $test;
    }
    else {
      break;
    }
  }
  return substr($comm, 0, -2);
}

/**
 * Parses a string in quotes
 *
 * @param $string A string specified with enclosing quotes
 * @return The string parsed from inside the quotes
 */
function _locale_import_parse_quoted($string) {
  if (substr($string, 0, 1) != substr($string, -1, 1)) {
    return FALSE;   // Start and end quotes must be the same
  }
  $quote = substr($string, 0, 1);
  $string = substr($string, 1, -1);
  if ($quote == '"') {        // Double quotes: strip slashes
    return stripcslashes($string);
  }
  elseif ($quote == "'") {  // Simple quote: return as-is
    return $string;
  }
  else {
    return FALSE;             // Unrecognized quote
  }
}
/**
 * @} End of "locale-api-import"
 */

/**
 * @defgroup locale-api-export Translation (template) export API.
 * @{
 */

/**
 * Exports a Portable Object (Template) file for a language
 *
 * @param $language
 *   Language code to generate the output for, or NULL if generating
 *   translation template.
 * @param $group
 *   Text group to export PO file from (eg. 'default' for interface translations)
 */
function _locale_export_po($language = NULL, $group = 'default') {
  global $user;
  $header = '';
  // Get language specific strings, or all strings
  if (isset($language)) {
    $meta = db_fetch_object(db_query("SELECT * FROM {languages} WHERE language = '%s'", $language));
    $result = db_query("SELECT s.lid, s.source, s.location, t.translation, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE t.language = '%s' AND s.textgroup = '%s' ORDER BY t.plid, t.plural", $language, $group);
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.location, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE s.textgroup = '%s' ORDER BY t.plid, t.plural", $group);
  }

  // Build array out of the database results
  $parent = array();
  while ($child = db_fetch_object($result)) {
    if ($child->source != '') {
      $parent[$child->lid]['comment'] = $child->location;
      $parent[$child->lid]['msgid'] = $child->source;
      $parent[$child->lid]['translation'] = isset($child->translation) ? $child->translation : '';
      if ($child->plid) {
        $parent[$child->lid]['child'] = 1;
        $parent[$child->plid]['plural'] = $child->lid;
      }
    }
  }

  // Generating Portable Object file for a language
  if (isset($language)) {
    $filename = $language .'.po';
    $header = "# $meta->name translation of ". variable_get('site_name', 'Drupal') ."\n";
    $header .= '# Copyright (c) '. date('Y') .' '. $user->name .' <'. $user->mail .">\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"Last-Translator: ". $user->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"Language-Team: ". $meta->name .' <'. $user->mail .">\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    if ($meta->formula && $meta->plurals) {
      $header .= "\"Plural-Forms: nplurals=". $meta->plurals ."; plural=". strtr($meta->formula, array('$' => '')) .";\\n\"\n";
    }
    $header .= "\n";
    watchdog('locale', 'Exported %locale translation file: %filename.', array('%locale' => $meta->name, '%filename' => $filename));
  }

  // Generating Portable Object Template
  else {
    $filename = 'drupal.pot';
    $header = "# LANGUAGE translation of PROJECT\n";
    $header .= "# Copyright (c) YEAR NAME <EMAIL@ADDRESS>\n";
    $header .= "#\n";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
    $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
    $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
    $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
    $header .= "\"MIME-Version: 1.0\\n\"\n";
    $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
    $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
    $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
    $header .= "\n";
    watchdog('locale', 'Exported translation file: %filename.', array('%filename' => $filename));
  }

  // Start download process
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");

  print $header;

  foreach ($parent as $lid => $message) {
    if (!isset($message['child'])) {
      if ($message['comment']) {
        print '#: '. $message['comment'] ."\n";
      }
      print 'msgid '. _locale_export_print($message['msgid']);
      if (!empty($message['plural'])) {
        $plural = $message['plural'];
        print 'msgid_plural '. _locale_export_print($parent[$plural]['msgid']);
        if (isset($language)) {
          $translation = $message['translation'];
          for ($i = 0; $i < $meta->plurals; $i++) {
            print 'msgstr['. $i .'] '. _locale_export_print($translation);
            if ($plural) {
              $translation = $parent[$plural]['translation'];
              if ($i > 1) {
                $translation = _locale_export_remove_plural($translation);
              }
              $plural = $parent[$plural]['plural'];
            }
            else {
              $translation = '';
            }
          }
        }
        else {
          print 'msgstr[0] ""'."\n";
          print 'msgstr[1] ""'."\n";
        }
      }
      else {
        if (isset($language)) {
          print 'msgstr '. _locale_export_print($message['translation']);
        }
        else {
          print 'msgstr ""'."\n";
        }
      }
      print "\n";
    }
  }
  die();
}

/**
 * Print out a string on multiple lines
 */
function _locale_export_print($str) {
  $stri = addcslashes($str, "\0..\37\\\"");
  $parts = array();

  // Cut text into several lines
  while ($stri != "") {
    $i = strpos($stri, "\\n");
    if ($i === FALSE) {
      $curstr = $stri;
      $stri = "";
    }
    else {
      $curstr = substr($stri, 0, $i + 2);
      $stri = substr($stri, $i + 2);
    }
    $curparts = explode("\n", _locale_export_wrap($curstr, 70));
    $parts = array_merge($parts, $curparts);
  }

  // Multiline string
  if (count($parts) > 1) {
    return "\"\"\n\"". implode("\"\n\"", $parts) ."\"\n";
  }
  // Single line string
  elseif (count($parts) == 1) {
    return "\"$parts[0]\"\n";
  }
  // No translation
  else {
    return "\"\"\n";
  }
}

/**
 * Custom word wrapping for Portable Object (Template) files.
 */
function _locale_export_wrap($str, $len) {
  $words = explode(' ', $str);
  $ret = array();

  $cur = "";
  $nstr = 1;
  while (count($words)) {
    $word = array_shift($words);
    if ($nstr) {
      $cur = $word;
      $nstr = 0;
    }
    elseif (strlen("$cur $word") > $len) {
      $ret[] = $cur ." ";
      $cur = $word;
    }
    else {
      $cur = "$cur $word";
    }
  }
  $ret[] = $cur;

  return implode("\n", $ret);
}

/**
 * Removes plural index information from a string
 */
function _locale_export_remove_plural($entry) {
  return preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
}
/**
 * @} End of "locale-api-export"
 */

/**
 * @defgroup locale-api-seek String search functions.
 * @{
 */

/**
 * Perform a string search and display results in a table
 */
function _locale_translate_seek() {
  $output = '';

  // We have at least one criterion to match
  if ($query = _locale_translate_seek_query()) {
    $join = "SELECT s.source, s.location, s.lid, s.textgroup, t.translation, t.language FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ";

    $arguments = array();
    // Compute LIKE section
    switch ($query['translation']) {
      case 'translated':
        $where = "WHERE (t.translation LIKE '%%%s%%' AND t.translation != '')";
        $orderby = "ORDER BY t.translation";
        $arguments[] = $query['string'];
        break;
      case 'untranslated':
        $where = "WHERE (s.source LIKE '%%%s%%' AND t.translation = '')";
        $orderby = "ORDER BY s.source";
        $arguments[] = $query['string'];
        break;
      case 'all' :
      default:
        $where = "WHERE (s.source LIKE '%%%s%%' OR t.translation LIKE '%%%s%%')";
        $orderby = '';
        $arguments[] = $query['string'];
        $arguments[] = $query['string'];
        break;
    }
    $grouplimit = '';
    if (!empty($query['group']) && $query['group'] != 'all') {
      $grouplimit = " AND s.textgroup = '%s'";
      $arguments[] = $query['group'];
    }

    switch ($query['language']) {
      // Force search in source strings
      case "en":
        $sql = $join ." WHERE s.source LIKE '%%%s%%' $grouplimit ORDER BY s.source";
        $arguments = array($query['string']); // $where is not used, discard its arguments
        if (!empty($grouplimit)) {
          $arguments[] = $query['group'];
        }
        break;
      // Search in all languages
      case "all":
        $sql = "$join $where $grouplimit $orderby";
        break;
      // Some different language
      default:
        $sql = "$join $where $grouplimit AND t.language = '%s' $orderby";
        $arguments[] = $query['language'];
    }

    $result = pager_query($sql, 50, 0, NULL, $arguments);

    $groups = module_invoke_all('locale', 'groups');
    $header = array(t('Text group'), t('String'), t('Languages'), array('data' => t('Operations'), 'colspan' => '2'));
    $arr = array();
    while ($locale = db_fetch_object($result)) {
      $arr[$locale->lid]['group'] = $groups[$locale->textgroup];
      $arr[$locale->lid]['languages'][$locale->language] = $locale->translation;
      $arr[$locale->lid]['location'] = $locale->location;
      $arr[$locale->lid]['source'] = $locale->source;
    }
    $rows = array();
    foreach ($arr as $lid => $value) {
      $rows[] = array($value['group'], array('data' => check_plain(truncate_utf8($value['source'], 150, FALSE, TRUE)) .'<br /><small>'. $value['location'] .'</small>'), array('data' => _locale_translate_language_list($value['languages']), 'align' => 'center'), array('data' => l(t('edit'), "admin/build/translate/edit/$lid"), 'class' => 'nowrap'), array('data' => l(t('delete'), "admin/build/translate/delete/$lid"), 'class' => 'nowrap'));
    }

    if (count($rows)) {
      $output .= theme('table', $header, $rows);
      if ($pager = theme('pager', NULL, 50)) {
        $output .= $pager;
      }
    }
    else {
      $output .= t('No strings found for your search.');
    }
  }

  return $output;
}

/**
 * Build array out of search criteria specified in request variables
 */
function _locale_translate_seek_query() {
  static $query = NULL;
  if (!isset($query)) {
    $query = array();
    $fields = array('string', 'language', 'translation', 'group');
    foreach ($fields as $field) {
      if (isset($_REQUEST[$field])) {
        $query[$field] = $_REQUEST[$field];
      }
    }
  }
  return $query;
}

/**
 * List languages in search result table
 */
function _locale_translate_language_list($translation) {
  // Add CSS
  drupal_add_css(drupal_get_path('module', 'locale') .'/locale.css', 'module', 'all', FALSE);

  $languages = language_list();
  unset($languages['en']);
  $output = '';
  foreach ($languages as $langcode => $language) {
    $output .= (!empty($translation[$langcode])) ? $langcode .' ' : "<em class=\"locale-untranslated\">$langcode</em> ";
  }

  return $output;
}
/**
 * @} End of "locale-api-seek"
 */

/**
 * @defgroup locale-api-predefined List of predefined languages
 * @{
 */

/**
 * Prepares the language code list for a select form item with only the unsupported ones
 */
function _locale_prepare_predefined_list() {
  $languages = language_list();
  $predefined = _locale_get_predefined_list();
  foreach ($predefined as $key => $value) {
    if (isset($languages[$key])) {
      unset($predefined[$key]);
      continue;
    }
    // Include native name in output, if possible
    if (count($value) == 2) {
      $tname = t($value[0]);
      $predefined[$key] = ($tname == $value[1]) ? $tname : "$tname ($value[1])";
    }
    else {
      $predefined[$key] = t($value[0]);
    }
  }
  asort($predefined);
  return $predefined;
}

/**
 * Some of the common languages with their English and native names
 *
 * Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
 */
function _locale_get_predefined_list() {
  return array(
    "aa" => array("Afar"),
    "ab" => array("Abkhazian", "аҧсуа бызшәа"),
    "ae" => array("Avestan"),
    "af" => array("Afrikaans"),
    "ak" => array("Akan"),
    "am" => array("Amharic", "አማርኛ"),
    "ar" => array("Arabic", /* Left-to-right marker "‭" */ "العربية", 1),
    "as" => array("Assamese"),
    "av" => array("Avar"),
    "ay" => array("Aymara"),
    "az" => array("Azerbaijani", "azərbaycan"),
    "ba" => array("Bashkir"),
    "be" => array("Belarusian", "Беларуская"),
    "bg" => array("Bulgarian", "Български"),
    "bh" => array("Bihari"),
    "bi" => array("Bislama"),
    "bm" => array("Bambara", "Bamanankan"),
    "bn" => array("Bengali"),
    "bo" => array("Tibetan"),
    "br" => array("Breton"),
    "bs" => array("Bosnian", "Bosanski"),
    "ca" => array("Catalan", "Català"),
    "ce" => array("Chechen"),
    "ch" => array("Chamorro"),
    "co" => array("Corsican"),
    "cr" => array("Cree"),
    "cs" => array("Czech", "Čeština"),
    "cu" => array("Old Slavonic"),
    "cv" => array("Chuvash"),
    "cy" => array("Welsh", "Cymraeg"),
    "da" => array("Danish", "Dansk"),
    "de" => array("German", "Deutsch"),
    "dv" => array("Maldivian"),
    "dz" => array("Bhutani"),
    "ee" => array("Ewe", "Ɛʋɛ"),
    "el" => array("Greek", "Ελληνικά"),
    "en" => array("English"),
    "eo" => array("Esperanto"),
    "es" => array("Spanish", "Español"),
    "et" => array("Estonian", "Eesti"),
    "eu" => array("Basque", "Euskera"),
    "fa" => array("Persian", /* Left-to-right marker "‭" */ "فارسی", 1),
    "ff" => array("Fulah", "Fulfulde"),
    "fi" => array("Finnish", "Suomi"),
    "fj" => array("Fiji"),
    "fo" => array("Faeroese"),
    "fr" => array("French", "Français"),
    "fy" => array("Frisian", "Frysk"),
    "ga" => array("Irish", "Gaeilge"),
    "gd" => array("Scots Gaelic"),
    "gl" => array("Galician", "Galego"),
    "gn" => array("Guarani"),
    "gu" => array("Gujarati"),
    "gv" => array("Manx"),
    "ha" => array("Hausa"),
    "he" => array("Hebrew", /* Left-to-right marker "‭" */ "עברית", 1),
    "hi" => array("Hindi", "हिन्दी"),
    "ho" => array("Hiri Motu"),
    "hr" => array("Croatian", "Hrvatski"),
    "hu" => array("Hungarian", "Magyar"),
    "hy" => array("Armenian", "Հայերեն"),
    "hz" => array("Herero"),
    "ia" => array("Interlingua"),
    "id" => array("Indonesian", "Bahasa Indonesia"),
    "ie" => array("Interlingue"),
    "ig" => array("Igbo"),
    "ik" => array("Inupiak"),
    "is" => array("Icelandic", "Íslenska"),
    "it" => array("Italian", "Italiano"),
    "iu" => array("Inuktitut"),
    "ja" => array("Japanese", "日本語"),
    "jv" => array("Javanese"),
    "ka" => array("Georgian"),
    "kg" => array("Kongo"),
    "ki" => array("Kikuyu"),
    "kj" => array("Kwanyama"),
    "kk" => array("Kazakh", "Қазақ"),
    "kl" => array("Greenlandic"),
    "km" => array("Cambodian"),
    "kn" => array("Kannada", "ಕನ್ನಡ"),
    "ko" => array("Korean", "한국어"),
    "kr" => array("Kanuri"),
    "ks" => array("Kashmiri"),
    "ku" => array("Kurdish", "Kurdî"),
    "kv" => array("Komi"),
    "kw" => array("Cornish"),
    "ky" => array("Kirghiz", "Кыргыз"),
    "la" => array("Latin", "Latina"),
    "lb" => array("Luxembourgish"),
    "lg" => array("Luganda"),
    "ln" => array("Lingala"),
    "lo" => array("Laothian"),
    "lt" => array("Lithuanian", "Lietuviškai"),
    "lv" => array("Latvian", "Latviešu"),
    "mg" => array("Malagasy"),
    "mh" => array("Marshallese"),
    "mi" => array("Maori"),
    "mk" => array("Macedonian", "Македонски"),
    "ml" => array("Malayalam", "മലയാളം"),
    "mn" => array("Mongolian"),
    "mo" => array("Moldavian"),
    "mr" => array("Marathi"),
    "ms" => array("Malay", "Bahasa Melayu"),
    "mt" => array("Maltese", "Malti"),
    "my" => array("Burmese"),
    "na" => array("Nauru"),
    "nd" => array("North Ndebele"),
    "ne" => array("Nepali"),
    "ng" => array("Ndonga"),
    "nl" => array("Dutch", "Nederlands"),
    "nb" => array("Norwegian Bokmål", "Bokmål"),
    "nn" => array("Norwegian Nynorsk", "Nynorsk"),
    "nr" => array("South Ndebele"),
    "nv" => array("Navajo"),
    "ny" => array("Chichewa"),
    "oc" => array("Occitan"),
    "om" => array("Oromo"),
    "or" => array("Oriya"),
    "os" => array("Ossetian"),
    "pa" => array("Punjabi"),
    "pi" => array("Pali"),
    "pl" => array("Polish", "Polski"),
    "ps" => array("Pashto", /* Left-to-right marker "‭" */ "پښتو", 1),
    "pt" => array("Portuguese, Portugal", "Português"),
    "pt-br" => array("Portuguese, Brazil", "Português"),
    "qu" => array("Quechua"),
    "rm" => array("Rhaeto-Romance"),
    "rn" => array("Kirundi"),
    "ro" => array("Romanian", "Română"),
    "ru" => array("Russian", "Русский"),
    "rw" => array("Kinyarwanda"),
    "sa" => array("Sanskrit"),
    "sc" => array("Sardinian"),
    "sd" => array("Sindhi"),
    "se" => array("Northern Sami"),
    "sg" => array("Sango"),
    "sh" => array("Serbo-Croatian"),
    "si" => array("Singhalese"),
    "sk" => array("Slovak", "Slovenčina"),
    "sl" => array("Slovenian", "Slovenščina"),
    "sm" => array("Samoan"),
    "sn" => array("Shona"),
    "so" => array("Somali"),
    "sq" => array("Albanian", "Shqip"),
    "sr" => array("Serbian", "Српски"),
    "ss" => array("Siswati"),
    "st" => array("Sesotho"),
    "su" => array("Sudanese"),
    "sv" => array("Swedish", "Svenska"),
    "sw" => array("Swahili", "Kiswahili"),
    "ta" => array("Tamil", "தமிழ்"),
    "te" => array("Telugu", "తెలుగు"),
    "tg" => array("Tajik"),
    "th" => array("Thai", "ภาษาไทย"),
    "ti" => array("Tigrinya"),
    "tk" => array("Turkmen"),
    "tl" => array("Tagalog"),
    "tn" => array("Setswana"),
    "to" => array("Tonga"),
    "tr" => array("Turkish", "Türkçe"),
    "ts" => array("Tsonga"),
    "tt" => array("Tatar", "Tatarça"),
    "tw" => array("Twi"),
    "ty" => array("Tahitian"),
    "ug" => array("Uighur"),
    "uk" => array("Ukrainian", "Українська"),
    "ur" => array("Urdu", /* Left-to-right marker "‭" */ "اردو", 1),
    "uz" => array("Uzbek", "o'zbek"),
    "ve" => array("Venda"),
    "vi" => array("Vietnamese", "Tiếng Việt"),
    "wo" => array("Wolof"),
    "xh" => array("Xhosa", "isiXhosa"),
    "yi" => array("Yiddish"),
    "yo" => array("Yoruba", "Yorùbá"),
    "za" => array("Zhuang"),
    "zh-hans" => array("Chinese, Simplified", "简体中文"),
    "zh-hant" => array("Chinese, Traditional", "繁體中文"),
    "zu" => array("Zulu", "isiZulu"),
  );
}
/**
 * @} End of "locale-api-languages-predefined"
 */
