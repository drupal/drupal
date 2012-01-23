<?php

/**
 * @file
 *   Administration functions for locale.module.
 */

define('LOCALE_JS_STRING', '(?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+');

/**
 * Translation import mode overwriting all existing translations
 * if new translated version available.
 */
define('LOCALE_IMPORT_OVERWRITE', 0);

/**
 * Translation import mode keeping existing translations and only
 * inserting new strings.
 */
define('LOCALE_IMPORT_KEEP', 1);

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
    // Language code should contain no markup, but is emitted
    // by radio and checkbox options.
    $langcode = check_plain($langcode);

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
    $form['direction'][$langcode] = array('#value' => ($language->direction == LANGUAGE_RTL ? t('Right to left') : t('Left to right')));
  }
  $form['enabled'] = array('#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $enabled,
  );
  $form['site_default'] = array('#type' => 'radios',
    '#options' => $options,
    '#default_value' => language_default('language'),
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  $form['#theme'] = 'locale_languages_overview_form';

  return $form;
}

/**
 * Theme the language overview form.
 *
 * @ingroup themeable
 */
function theme_locale_languages_overview_form($form) {
  $default = language_default();
  foreach ($form['name'] as $key => $element) {
    // Do not take form control structures.
    if (is_array($element) && element_child($key)) {
      // Disable checkbox for the default language, because it cannot be disabled.
      if ($key == $default->language) {
        $form['enabled'][$key]['#attributes']['disabled'] = 'disabled';
      }
      $rows[] = array(
        array('data' => drupal_render($form['enabled'][$key]), 'align' => 'center'),
        check_plain($key),
        '<strong>'. drupal_render($form['name'][$key]) .'</strong>',
        drupal_render($form['native'][$key]),
        drupal_render($form['direction'][$key]),
        drupal_render($form['site_default'][$key]),
        drupal_render($form['weight'][$key]),
        l(t('edit'), 'admin/settings/language/edit/'. $key) . (($key != 'en' && $key != $default->language) ? ' '. l(t('delete'), 'admin/settings/language/delete/'. $key) : '')
      );
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
function locale_languages_overview_form_submit($form, &$form_state) {
  $languages = language_list();
  $default = language_default();
  $enabled_count = 0;
  foreach ($languages as $langcode => $language) {
    if ($form_state['values']['site_default'] == $langcode || $default->language == $langcode) {
      // Automatically enable the default language and the language
      // which was default previously (because we will not get the
      // value from that disabled checkox).
      $form_state['values']['enabled'][$langcode] = 1;
    }
    if ($form_state['values']['enabled'][$langcode]) {
      $enabled_count++;
      $language->enabled = 1;
    }
    else {
      $language->enabled = 0;
    }
    $language->weight = $form_state['values']['weight'][$langcode];
    db_query("UPDATE {languages} SET enabled = %d, weight = %d WHERE language = '%s'", $language->enabled, $language->weight, $langcode);
    $languages[$langcode] = $language;
  }
  drupal_set_message(t('Configuration saved.'));
  variable_set('language_default', $languages[$form_state['values']['site_default']]);
  variable_set('language_count', $enabled_count);

  // Changing the language settings impacts the interface.
  cache_clear_all('*', 'cache_page', TRUE);

  $form_state['redirect'] = 'admin/settings/language';
  return;
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
    '#description' => t('Select the desired language and click the <em>Add language</em> button. (Use the <em>Custom language</em> options if your desired language does not appear in this list.)'),
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
    '#collapsed' => TRUE,
  );
  _locale_languages_common_controls($form['custom language']);
  $form['custom language']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add custom language')
  );
  // Reuse the validation and submit functions of the predefined language setup form.
  $form['#submit'][] = 'locale_languages_predefined_form_submit';
  $form['#validate'][] = 'locale_languages_predefined_form_validate';
  return $form;
}

/**
 * Editing screen for a particular language.
 *
 * @param $langcode
 *   Language code of the language to edit.
 */
function locale_languages_edit_form(&$form_state, $langcode) {
  if ($language = db_fetch_object(db_query("SELECT * FROM {languages} WHERE language = '%s'", $langcode))) {
    $form = array();
    _locale_languages_common_controls($form, $language);
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save language')
    );
    $form['#submit'][] = 'locale_languages_edit_form_submit';
    $form['#validate'][] = 'locale_languages_edit_form_validate';
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
      '#description' => t('<a href="@rfc4646">RFC 4646</a> compliant language identifier. Language codes typically use a country code, and optionally, a script or regional variant name. <em>Examples: "en", "en-US" and "zh-Hant".</em>', array('@rfc4646' => 'http://www.ietf.org/rfc/rfc4646.txt')),
    );
  }
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Language name in English'),
    '#maxlength' => 64,
    '#default_value' => @$language->name,
    '#required' => TRUE,
    '#description' => t('Name of the language in English. Will be available for translation in all languages.'),
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
    '#description' => t('Language code or other custom string for pattern matching within the path. With language negotiation set to <em>Path prefix only</em> or <em>Path prefix with language fallback</em>, this site is presented in this language when the Path prefix value matches an element in the path. For the default language, this value may be left blank. <strong>Modifying this value will break existing URLs and should be used with caution in a production environment.</strong> <em>Example: Specifying "deutsch" as the path prefix for German results in URLs in the form "www.example.com/deutsch/node".</em>')
  );
  $form['domain'] = array('#type' => 'textfield',
    '#title' => t('Language domain'),
    '#maxlength' => 128,
    '#default_value' => @$language->domain,
    '#description' => t('Language-specific URL, with protocol. With language negotiation set to <em>Domain name only</em>, the site is presented in this language when the URL accessing the site references this domain. For the default language, this value may be left blank. <strong>This value must include a protocol as part of the string.</strong> <em>Example: Specifying "http://example.de" or "http://de.example.com" as language domains for German results in URLs in the forms "http://example.de/node" and "http://de.example.com/node", respectively.</em>'),
  );
  $form['direction'] = array('#type' => 'radios',
    '#title' => t('Direction'),
    '#required' => TRUE,
    '#description' => t('Direction that text in this language is presented.'),
    '#default_value' => @$language->direction,
    '#options' => array(LANGUAGE_LTR => t('Left to right'), LANGUAGE_RTL => t('Right to left'))
  );
  return $form;
}

/**
 * Validate the language addition form.
 */
function locale_languages_predefined_form_validate($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  if ($duplicate = db_result(db_query("SELECT COUNT(*) FROM {languages} WHERE language = '%s'", $langcode)) != 0) {
    form_set_error('langcode', t('The language %language (%code) already exists.', array('%language' => $form_state['values']['name'], '%code' => $langcode)));
  }

  if (!isset($form_state['values']['name'])) {
    // Predefined language selection.
    $predefined = _locale_get_predefined_list();
    if (!isset($predefined[$langcode])) {
      form_set_error('langcode', t('Invalid language code.'));
    }
  }
  else {
    // Reuse the editing form validation routine if we add a custom language.
    locale_languages_edit_form_validate($form, $form_state);
  }
}

/**
 * Process the language addition form submission.
 */
function locale_languages_predefined_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  if (isset($form_state['values']['name'])) {
    // Custom language form.
    locale_add_language($langcode, $form_state['values']['name'], $form_state['values']['native'], $form_state['values']['direction'], $form_state['values']['domain'], $form_state['values']['prefix']);
    drupal_set_message(t('The language %language has been created and can now be used. More information is available on the <a href="@locale-help">help screen</a>.', array('%language' => t($form_state['values']['name']), '@locale-help' => url('admin/help/locale'))));
  }
  else {
    // Predefined language selection.
    $predefined = _locale_get_predefined_list();
    locale_add_language($langcode);
    drupal_set_message(t('The language %language has been created and can now be used. More information is available on the <a href="@locale-help">help screen</a>.', array('%language' => t($predefined[$langcode][0]), '@locale-help' => url('admin/help/locale'))));
  }

  // See if we have language files to import for the newly added
  // language, collect and import them.
  if ($batch = locale_batch_by_language($langcode, '_locale_batch_language_finished')) {
    batch_set($batch);
  }

  $form_state['redirect'] = 'admin/settings/language';
  return;
}

/**
 * Validate the language editing form. Reused for custom language addition too.
 */
function locale_languages_edit_form_validate($form, &$form_state) {
  // Validate that the name, native, and langcode variables are safe.
  if (preg_match('/["<>\']/', $form_state['values']['langcode'])) {
    form_set_error('langcode', t('The characters &lt;, &gt;, " and \' are not allowed in the language code field.'));
  }
  if (preg_match('/["<>\']/', $form_state['values']['name'])) {
    form_set_error('name', t('The characters &lt;, &gt;, " and \' are not allowed in the language name in English field.'));
  }
  if (preg_match('/["<>\']/', $form_state['values']['native'])) {
    form_set_error('native', t('The characters &lt;, &gt;, " and \' are not allowed in the native language name field.'));
  }

  if (!empty($form_state['values']['domain']) && !empty($form_state['values']['prefix'])) {
    form_set_error('prefix', t('Domain and path prefix values should not be set at the same time.'));
  }
  if (!empty($form_state['values']['domain']) && $duplicate = db_fetch_object(db_query("SELECT language FROM {languages} WHERE domain = '%s' AND language != '%s'", $form_state['values']['domain'], $form_state['values']['langcode']))) {
    form_set_error('domain', t('The domain (%domain) is already tied to a language (%language).', array('%domain' => $form_state['values']['domain'], '%language' => $duplicate->language)));
  }
  if (empty($form_state['values']['prefix']) && language_default('language') != $form_state['values']['langcode'] && empty($form_state['values']['domain'])) {
    form_set_error('prefix', t('Only the default language can have both the domain and prefix empty.'));
  }
  if (!empty($form_state['values']['prefix']) && $duplicate = db_fetch_object(db_query("SELECT language FROM {languages} WHERE prefix = '%s' AND language != '%s'", $form_state['values']['prefix'], $form_state['values']['langcode']))) {
    form_set_error('prefix', t('The prefix (%prefix) is already tied to a language (%language).', array('%prefix' => $form_state['values']['prefix'], '%language' => $duplicate->language)));
  }
}

/**
 * Process the language editing form submission.
 */
function locale_languages_edit_form_submit($form, &$form_state) {
  db_query("UPDATE {languages} SET name = '%s', native = '%s', domain = '%s', prefix = '%s', direction = %d WHERE language = '%s'", $form_state['values']['name'], $form_state['values']['native'], $form_state['values']['domain'], $form_state['values']['prefix'], $form_state['values']['direction'], $form_state['values']['langcode']);
  $default = language_default();
  if ($default->language == $form_state['values']['langcode']) {
    $properties = array('name', 'native', 'direction', 'enabled', 'plurals', 'formula', 'domain', 'prefix', 'weight');
    foreach ($properties as $keyname) {
      if (isset($form_state['values'][$keyname])) {
        $default->$keyname = $form_state['values'][$keyname];
      }
    }
    variable_set('language_default', $default);
  }
  $form_state['redirect'] = 'admin/settings/language';
  return;
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
function locale_languages_delete_form(&$form_state, $langcode) {

  // Do not allow deletion of English locale.
  if ($langcode == 'en') {
    drupal_set_message(t('The English language cannot be deleted.'));
    drupal_goto('admin/settings/language');
  }

  if (language_default('language') == $langcode) {
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
function locale_languages_delete_form_submit($form, &$form_state) {
  $languages = language_list();
  if (isset($languages[$form_state['values']['langcode']])) {
    // Remove translations first.
    db_query("DELETE FROM {locales_target} WHERE language = '%s'", $form_state['values']['langcode']);
    cache_clear_all('locale:'. $form_state['values']['langcode'], 'cache');
    // With no translations, this removes existing JavaScript translations file.
    _locale_rebuild_js($form_state['values']['langcode']);
    // Remove the language.
    db_query("DELETE FROM {languages} WHERE language = '%s'", $form_state['values']['langcode']);
    db_query("UPDATE {node} SET language = '' WHERE language = '%s'", $form_state['values']['langcode']);
    $variables = array('%locale' => $languages[$form_state['values']['langcode']]->name);
    drupal_set_message(t('The language %locale has been removed.', $variables));
    watchdog('locale', 'The language %locale has been removed.', $variables);
  }

  // Changing the language settings impacts the interface:
  cache_clear_all('*', 'cache_page', TRUE);

  $form_state['redirect'] = 'admin/settings/language';
  return;
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
      LANGUAGE_NEGOTIATION_NONE => t('None.'),
      LANGUAGE_NEGOTIATION_PATH_DEFAULT => t('Path prefix only.'),
      LANGUAGE_NEGOTIATION_PATH => t('Path prefix with language fallback.'),
      LANGUAGE_NEGOTIATION_DOMAIN => t('Domain name only.')),
    '#default_value' => variable_get('language_negotiation', LANGUAGE_NEGOTIATION_NONE),
    '#description' => t("Select the mechanism used to determine your site's presentation language. <strong>Modifying this setting may break all incoming URLs and should be used with caution in a production environment.</strong>")
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
function locale_languages_configure_form_submit($form, &$form_state) {
  variable_set('language_negotiation', $form_state['values']['language_negotiation']);
  drupal_set_message(t('Language negotiation configuration saved.'));
  $form_state['redirect'] = 'admin/settings/language';
  return;
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

  // Set up overview table with default values, ensuring common order for values.
  $rows = array();
  foreach ($languages as $langcode => $language) {
    $rows[$langcode] = array('name' => ($langcode == 'en' ? t('English (built-in)') : t($language->name)));
    foreach ($groups as $group => $name) {
      $rows[$langcode][$group] = ($langcode == 'en' ? t('n/a') : '0/'. (isset($groupsums[$group]) ? $groupsums[$group] : 0) .' (0%)');
    }
  }

  // Languages with at least one record in the locale table.
  $translations = db_query("SELECT COUNT(*) AS translation, t.language, s.textgroup FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid GROUP BY textgroup, language");
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
  $raw_languages = locale_language_list('name', TRUE);
  unset($raw_languages['en']);
  // Sanitize the values to be used in radios.
  $languages = array();
  foreach ($raw_languages as $key => $value) {
    $languages[check_plain($key)] = check_plain($value);
  }

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
  $form['search']['language'] = array(
    // Change type of form widget if more the 5 options will
    // be present (2 of the options are added below).
    '#type' => (count($languages) <= 3 ? 'radios' : 'select'),
    '#title' => t('Language'),
    '#default_value' => (!empty($query['language']) ? $query['language'] : 'all'),
    '#options' => array_merge(array('all' => t('All languages'), 'en' => t('English (provided by Drupal)')), $languages),
  );
  $form['search']['translation'] = array('#type' => 'radios',
    '#title' => t('Search in'),
    '#default_value' => (!empty($query['translation']) ? $query['translation'] : 'all'),
    '#options' => array('all' => t('Both translated and untranslated strings'), 'translated' => t('Only translated strings'), 'untranslated' => t('Only untranslated strings')),
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
    '#description' => t('A Gettext Portable Object (<em>.po</em>) file.'),
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
    '#default_value' => LOCALE_IMPORT_KEEP,
    '#options' => array(
      LOCALE_IMPORT_OVERWRITE => t('Strings in the uploaded file replace existing ones, new ones are added'),
      LOCALE_IMPORT_KEEP => t('Existing strings are kept, only new strings are added')
    ),
  );
  $form['import']['submit'] = array('#type' => 'submit', '#value' => t('Import'));
  $form['#attributes']['enctype'] = 'multipart/form-data';

  return $form;
}

/**
 * Process the locale import form submission.
 */
function locale_translate_import_form_submit($form, &$form_state) {
  // Ensure we have the file uploaded
  if ($file = file_save_upload('file')) {

    // Add language, if not yet supported
    $languages = language_list('language', TRUE);
    $langcode = $form_state['values']['langcode'];
    if (!isset($languages[$langcode])) {
      $predefined = _locale_get_predefined_list();
      locale_add_language($langcode);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($predefined[$langcode][0]))));
    }

    // Now import strings into the language
    if ($ret = _locale_import_po($file, $langcode, $form_state['values']['mode'], $form_state['values']['group']) == FALSE) {
      $variables = array('%filename' => $file->filename);
      drupal_set_message(t('The translation import of %filename failed.', $variables), 'error');
      watchdog('locale', 'The translation import of %filename failed.', $variables, WATCHDOG_ERROR);
    }
  }
  else {
    drupal_set_message(t('File to import not found.'), 'error');
    $form_state['redirect'] = 'admin/build/translate/import';
    return;
  }

  $form_state['redirect'] = 'admin/build/translate';
  return;
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
function locale_translate_export_po_form(&$form_state, $names) {
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export translation'),
    '#collapsible' => TRUE,
  );
  $form['export']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $names,
    '#description' => t('Select the language to export in Gettext Portable Object (<em>.po</em>) format.'),
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
    '#description' => t('Generate a Gettext Portable Object Template (<em>.pot</em>) file with all strings from the Drupal locale database.'),
  );
  $form['export']['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  // Reuse PO export submission callback.
  $form['#submit'][] = 'locale_translate_export_po_form_submit';
  $form['#validate'][] = 'locale_translate_export_po_form_validate';
  return $form;
}

/**
 * Process a translation (or template) export form submission.
 */
function locale_translate_export_po_form_submit($form, &$form_state) {
  // If template is required, language code is not given.
  $language = NULL;
  if (isset($form_state['values']['langcode'])) {
    $languages = language_list();
    $language = $languages[$form_state['values']['langcode']];
  }
  _locale_export_po($language, _locale_export_po_generate($language, _locale_export_get_strings($language, $form_state['values']['group'])));
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
function locale_translate_edit_form(&$form_state, $lid) {
  // Fetch source string, if possible.
  $source = db_fetch_object(db_query('SELECT source, textgroup, location FROM {locales_source} WHERE lid = %d', $lid));
  if (!$source) {
    drupal_set_message(t('String not found.'), 'error');
    drupal_goto('admin/build/translate/search');
  }

  // Add original text to the top and some values for form altering.
  $form = array(
    'original' => array(
      '#type'  => 'item',
      '#title' => t('Original text'),
      '#value' => check_plain(wordwrap($source->source, 0)),
    ),
    'lid' => array(
      '#type'  => 'value',
      '#value' => $lid
    ),
    'textgroup' => array(
      '#type'  => 'value',
      '#value' => $source->textgroup,
    ),
    'location' => array(
      '#type'  => 'value',
      '#value' => $source->location
    ),
  );

  // Include default form controls with empty values for all languages.
  // This ensures that the languages are always in the same order in forms.
  $languages = language_list();
  $default = language_default();
  // We don't need the default language value, that value is in $source.
  $omit = $source->textgroup == 'default' ? 'en' : $default->language;
  unset($languages[($omit)]);
  $form['translations'] = array('#tree' => TRUE);
  // Approximate the number of rows to use in the default textarea.
  $rows = min(ceil(str_word_count($source->source) / 12), 10);
  foreach ($languages as $langcode => $language) {
    $form['translations'][$langcode] = array(
      '#type' => 'textarea',
      '#title' => t($language->name),
      '#rows' => $rows,
      '#default_value' => '',
    );
  }

  // Fetch translations and fill in default values in the form.
  $result = db_query("SELECT DISTINCT translation, language FROM {locales_target} WHERE lid = %d AND language != '%s'", $lid, $omit);
  while ($translation = db_fetch_object($result)) {
    $form['translations'][$translation->language]['#default_value'] = $translation->translation;
  }

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save translations'));
  return $form;
}

/**
 * Check that a string is safe to be added or imported as a translation.
 *
 * This test can be used to detect possibly bad translation strings. It should
 * not have any false positives. But it is only a test, not a transformation,
 * as it destroys valid HTML. We cannot reliably filter translation strings
 * on inport becuase some strings are irreversibly corrupted. For example,
 * a &amp; in the translation would get encoded to &amp;amp; by filter_xss()
 * before being put in the database, and thus would be displayed incorrectly.
 *
 * The allowed tag list is like filter_xss_admin(), but omitting div and img as
 * not needed for translation and likely to cause layout issues (div) or a
 * possible attack vector (img).
 */
function locale_string_is_safe($string) {
  return decode_entities($string) == decode_entities(filter_xss($string, array('a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'ul', 'var')));
}

/**
 * Validate string editing form submissions.
 */
function locale_translate_edit_form_validate($form, &$form_state) {
  // Locale string check is needed for default textgroup only.
  $safe_check_needed = $form_state['values']['textgroup'] == 'default';
  foreach ($form_state['values']['translations'] as $key => $value) {
    if ($safe_check_needed && !locale_string_is_safe($value)) {
      form_set_error('translations', t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
      watchdog('locale', 'Attempted submission of a translation string with disallowed HTML: %string', array('%string' => $value), WATCHDOG_WARNING);
    }
  }
}

/**
 * Process string editing form submissions.
 *
 * Saves all translations of one string submitted from a form.
 */
function locale_translate_edit_form_submit($form, &$form_state) {
  $lid = $form_state['values']['lid'];
  foreach ($form_state['values']['translations'] as $key => $value) {
    $translation = db_result(db_query("SELECT translation FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $key));
    if (!empty($value)) {
      // Only update or insert if we have a value to use.
      if (!empty($translation)) {
        db_query("UPDATE {locales_target} SET translation = '%s' WHERE lid = %d AND language = '%s'", $value, $lid, $key);
      }
      else {
        db_query("INSERT INTO {locales_target} (lid, translation, language) VALUES (%d, '%s', '%s')", $lid, $value, $key);
      }
    }
    elseif (!empty($translation)) {
      // Empty translation entered: remove existing entry from database.
      db_query("DELETE FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $key);
    }

    // Force JavaScript translation file recreation for this language.
    _locale_invalidate_js($key);
  }

  drupal_set_message(t('The string has been saved.'));

  // Clear locale cache.
  _locale_invalidate_js();
  cache_clear_all('locale:', 'cache', TRUE);

  $form_state['redirect'] = 'admin/build/translate/search';
  return;
}
/**
 * @} End of "locale-translate-edit"
 */

/**
 * @defgroup locale-translate-delete Translation delete interface.
 * @{
 */

/**
 * String deletion confirmation page.
 */
function locale_translate_delete_page($lid) {
  if ($source = db_fetch_object(db_query('SELECT * FROM {locales_source} WHERE lid = %d', $lid))) {
    return drupal_get_form('locale_translate_delete_form', $source);
  }
  else {
    return drupal_not_found();
  }
}

/**
 * User interface for the string deletion confirmation screen.
 */
function locale_translate_delete_form(&$form_state, $source) {
  $form['lid'] = array('#type' => 'value', '#value' => $source->lid);
  return confirm_form($form, t('Are you sure you want to delete the string "%source"?', array('%source' => $source->source)), 'admin/build/translate/search', t('Deleting the string will remove all translations of this string in all languages. This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Process string deletion submissions.
 */
function locale_translate_delete_form_submit($form, &$form_state) {
  db_query('DELETE FROM {locales_source} WHERE lid = %d', $form_state['values']['lid']);
  db_query('DELETE FROM {locales_target} WHERE lid = %d', $form_state['values']['lid']);
  // Force JavaScript translation file recreation for all languages.
  _locale_invalidate_js();
  cache_clear_all('locale:', 'cache', TRUE);
  drupal_set_message(t('The string has been removed.'));
  $form_state['redirect'] = 'admin/build/translate/search';
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
 * @param $enabled
 *   Optionally TRUE to enable the language when created or FALSE to disable.
 * @param $default
 *   Optionally set this language to be the default.
 */
function locale_add_language($langcode, $name = NULL, $native = NULL, $direction = LANGUAGE_LTR, $domain = '', $prefix = '', $enabled = TRUE, $default = FALSE) {
  // Default prefix on language code.
  if (empty($prefix)) {
    $prefix = $langcode;
  }

  // If name was not set, we add a predefined language.
  if (!isset($name)) {
    $predefined = _locale_get_predefined_list();
    $name = $predefined[$langcode][0];
    $native = isset($predefined[$langcode][1]) ? $predefined[$langcode][1] : $predefined[$langcode][0];
    $direction = isset($predefined[$langcode][2]) ? $predefined[$langcode][2] : LANGUAGE_LTR;
  }

  db_query("INSERT INTO {languages} (language, name, native, direction, domain, prefix, enabled) VALUES ('%s', '%s', '%s', %d, '%s', '%s', %d)", $langcode, $name, $native, $direction, $domain, $prefix, $enabled);

  // Only set it as default if enabled.
  if ($enabled && $default) {
    variable_set('language_default', (object) array('language' => $langcode, 'name' => $name, 'native' => $native, 'direction' => $direction, 'enabled' => (int) $enabled, 'plurals' => 0, 'formula' => '', 'domain' => '', 'prefix' => $prefix, 'weight' => 0, 'javascript' => ''));
  }

  if ($enabled) {
    // Increment enabled language count if we are adding an enabled language.
    variable_set('language_count', variable_get('language_count', 1) + 1);
  }

  // Force JavaScript translation file creation for the newly added language.
  _locale_invalidate_js($langcode);

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
 * @param $langcode
 *   Language code
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_po($file, $langcode, $mode, $group = NULL) {
  // Try to allocate enough time to parse and import the data.
  if (function_exists('set_time_limit')) {
    @set_time_limit(240);
  }

  // Check if we have the language already in the database.
  if (!db_fetch_object(db_query("SELECT language FROM {languages} WHERE language = '%s'", $langcode))) {
    drupal_set_message(t('The language selected for import is not supported.'), 'error');
    return FALSE;
  }

  // Get strings from file (returns on failure after a partial import, or on success)
  $status = _locale_import_read_po('db-store', $file, $mode, $langcode, $group);
  if ($status === FALSE) {
    // Error messages are set in _locale_import_read_po().
    return FALSE;
  }

  // Get status information on import process.
  list($headerdone, $additions, $updates, $deletes, $skips) = _locale_import_one_string('db-report');

  if (!$headerdone) {
    drupal_set_message(t('The translation file %filename appears to have a missing or malformed header.', array('%filename' => $file->filename)), 'error');
  }

  // Clear cache and force refresh of JavaScript translations.
  _locale_invalidate_js($langcode);
  cache_clear_all('locale:', 'cache', TRUE);

  // Rebuild the menu, strings may have changed.
  menu_rebuild();

  drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)));
  watchdog('locale', 'Imported %file into %locale: %number new strings added, %update updated and %delete removed.', array('%file' => $file->filename, '%locale' => $langcode, '%number' => $additions, '%update' => $updates, '%delete' => $deletes));
  if ($skips) {
    $skip_message = format_plural($skips, 'One translation string was skipped because it contains disallowed HTML.', '@count translation strings were skipped because they contain disallowed HTML.');
    drupal_set_message($skip_message);
    watchdog('locale', $skip_message, NULL, WATCHDOG_WARNING);
  }
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
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE
 * @param $lang
 *   Language code
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_read_po($op, $file, $mode = NULL, $lang = NULL, $group = 'default') {

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
    if ($lineno == 0) {
      // The first line might come with a UTF-8 BOM, which should be removed.
      $line = str_replace("\xEF\xBB\xBF", '', $line);
    }
    $lineno++;
    $line = trim(strtr($line, array("\\\n" => "")));

    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
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
      if (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        _locale_import_message('The translation file %filename contains an error: "msgid" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
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
    _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
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
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE
 * @param $lang
 *   Language to store the string in
 * @param $file
 *   Object representation of file being imported, only required when op is 'db-store'
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface translations)
 */
function _locale_import_one_string($op, $value = NULL, $mode = NULL, $lang = NULL, $file = NULL, $group = 'default') {
  static $report = array('additions' => 0, 'updates' => 0, 'deletes' => 0, 'skips' => 0);
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
      return array($headerdone, $report['additions'], $report['updates'], $report['deletes'], $report['skips']);

    // Store the string we got in the database.
    case 'db-store':
      // We got header information.
      if ($value['msgid'] == '') {
        $languages = language_list();
        if (($mode != LOCALE_IMPORT_KEEP) || empty($languages[$lang]->plurals)) {
          // Since we only need to parse the header if we ought to update the
          // plural formula, only run this if we don't need to keep existing
          // data untouched or if we don't have an existing plural formula.
          $header = _locale_import_parse_header($value['msgstr']);

          // Get the plural formula and update in database.
          if (isset($header["Plural-Forms"]) && $p = _locale_import_parse_plural_forms($header["Plural-Forms"], $file->filename)) {
            list($nplurals, $plural) = $p;
            db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", $nplurals, $plural, $lang);
          }
          else {
            db_query("UPDATE {languages} SET plurals = %d, formula = '%s' WHERE language = '%s'", 0, '', $lang);
          }
        }
        $headerdone = TRUE;
      }

      else {
        // Some real string to import.
        $comments = _locale_import_shorten_comments(empty($value['#']) ? array() : $value['#']);

        if (strpos($value['msgid'], "\0")) {
          // This string has plural versions.
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
            $plid = _locale_import_one_string_db($report, $lang, $english[$key], $trans, $group, $comments, $mode, $plid, $key);
          }
        }

        else {
          // A simple string to import.
          $english = $value['msgid'];
          $translation = $value['msgstr'];
          _locale_import_one_string_db($report, $lang, $english, $translation, $group, $comments, $mode);
        }
      }
  } // end of db-store operation
}

/**
 * Import one string into the database.
 *
 * @param $report
 *   Report array summarizing the number of changes done in the form:
 *   array(inserts, updates, deletes).
 * @param $langcode
 *   Language code to import string into.
 * @param $source
 *   Source string.
 * @param $translation
 *   Translation to language specified in $langcode.
 * @param $textgroup
 *   Name of textgroup to store translation in.
 * @param $location
 *   Location value to save with source string.
 * @param $mode
 *   Import mode to use, LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE.
 * @param $plid
 *   Optional plural ID to use.
 * @param $plural
 *   Optional plural value to use.
 * @return
 *   The string ID of the existing string modified or the new string added.
 */
function _locale_import_one_string_db(&$report, $langcode, $source, $translation, $textgroup, $location, $mode, $plid = NULL, $plural = NULL) {
  $lid = db_result(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $source, $textgroup));

  if (!empty($translation)) {
     // Skip this string unless it passes a check for dangerous code.
     // Text groups other than default still can contain HTML tags
     // (i.e. translatable blocks).
     if ($textgroup == "default" && !locale_string_is_safe($translation)) {
       $report['skips']++;
       $lid = 0;
     }
     elseif ($lid) {
      // We have this source string saved already.
      db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $location, $lid);
      $exists = (bool) db_result(db_query("SELECT lid FROM {locales_target} WHERE lid = %d AND language = '%s'", $lid, $langcode));
      if (!$exists) {
        // No translation in this language.
        db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $langcode, $translation, $plid, $plural);
        $report['additions']++;
      }
      else if ($mode == LOCALE_IMPORT_OVERWRITE) {
        // Translation exists, only overwrite if instructed.
        db_query("UPDATE {locales_target} SET translation = '%s', plid = %d, plural = %d WHERE language = '%s' AND lid = %d", $translation, $plid, $plural, $langcode, $lid);
        $report['updates']++;
      }
    }
    else {
      // No such source string in the database yet.
      db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', '%s')", $location, $source, $textgroup);
      $lid = db_result(db_query("SELECT lid FROM {locales_source} WHERE source = '%s' AND textgroup = '%s'", $source, $textgroup));
      db_query("INSERT INTO {locales_target} (lid, language, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $langcode, $translation, $plid, $plural);
      $report['additions']++;
    }
  }
  elseif ($mode == LOCALE_IMPORT_OVERWRITE) {
    // Empty translation, remove existing if instructed.
    db_query("DELETE FROM {locales_target} WHERE language = '%s' AND lid = %d AND plid = %d AND plural = %d", $langcode, $lid, $plid, $plural);
    $report['deletes']++;
  }

  return $lid;
}

/**
 * Parses a Gettext Portable Object file header
 *
 * @param $header
 *   A string containing the complete header
 * @return
 *   An associative array of key-value pairs
 */
function _locale_import_parse_header($header) {
  $header_parsed = array();
  $lines = array_map('trim', explode("\n", $header));
  foreach ($lines as $line) {
    if ($line) {
      list($tag, $contents) = explode(":", $line, 2);
      $header_parsed[trim($tag)] = trim($contents);
    }
  }
  return $header_parsed;
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
 * @param $string
 *   A string containing the arithmetic formula
 * @return
 *   The PHP version of the formula
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
 * @param $string
 *   A string containing the arithmetic formula
 * @return
 *   The PHP version of the formula
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
 * @param $entry
 *   An array element
 * @param $key
 *   Index of the array element
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
 * @param $comment
 *   An array of strings containing a comment
 * @return
 *   Short one string version of the comment
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
  return trim(substr($comm, 0, -2));
}

/**
 * Parses a string in quotes
 *
 * @param $string
 *   A string specified with enclosing quotes
 * @return
 *   The string parsed from inside the quotes
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
 * Parses a JavaScript file, extracts strings wrapped in Drupal.t() and
 * Drupal.formatPlural() and inserts them into the database.
 */
function _locale_parse_js_file($filepath) {
  global $language;

  // Load the JavaScript file.
  $file = file_get_contents($filepath);

  // Match all calls to Drupal.t() in an array.
  // Note: \s also matches newlines with the 's' modifier.
  preg_match_all('~[^\w]Drupal\s*\.\s*t\s*\(\s*('. LOCALE_JS_STRING .')\s*[,\)]~s', $file, $t_matches);

  // Match all Drupal.formatPlural() calls in another array.
  preg_match_all('~[^\w]Drupal\s*\.\s*formatPlural\s*\(\s*.+?\s*,\s*('. LOCALE_JS_STRING .')\s*,\s*((?:(?:\'(?:\\\\\'|[^\'])*@count(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*@count(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+)\s*[,\)]~s', $file, $plural_matches);

  // Loop through all matches and process them.
  $all_matches = array_merge($plural_matches[1], $t_matches[1]);
  foreach ($all_matches as $key => $string) {
    $strings = array($string);

    // If there is also a plural version of this string, add it to the strings array.
    if (isset($plural_matches[2][$key])) {
      $strings[] = $plural_matches[2][$key];
    }

    foreach ($strings as $key => $string) {
      // Remove the quotes and string concatenations from the string.
      $string = implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($string, 1, -1)));

      $result = db_query("SELECT lid, location FROM {locales_source} WHERE source = '%s' AND textgroup = 'default'", $string);
      if ($source = db_fetch_object($result)) {
        // We already have this source string and now have to add the location
        // to the location column, if this file is not yet present in there.
        $locations = preg_split('~\s*;\s*~', $source->location);

        if (!in_array($filepath, $locations)) {
          $locations[] = $filepath;
          $locations = implode('; ', $locations);

          // Save the new locations string to the database.
          db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $locations, $source->lid);
        }
      }
      else {
        // We don't have the source string yet, thus we insert it into the database.
        db_query("INSERT INTO {locales_source} (location, source, textgroup) VALUES ('%s', '%s', 'default')", $filepath, $string);
      }
    }
  }
}

/**
 * @defgroup locale-api-export Translation (template) export API.
 * @{
 */

/**
 * Generates a structured array of all strings with translations in
 * $language, if given. This array can be used to generate an export
 * of the string in the database.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $group
 *   Text group to export PO file from (eg. 'default' for interface translations)
 */
function _locale_export_get_strings($language = NULL, $group = 'default') {
  if (isset($language)) {
    $result = db_query("SELECT s.lid, s.source, s.location, t.translation, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid AND t.language = '%s' WHERE s.textgroup = '%s' ORDER BY t.plid, t.plural", $language->language, $group);
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.location, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid WHERE s.textgroup = '%s' ORDER BY t.plid, t.plural", $group);
  }
  $strings = array();
  while ($child = db_fetch_object($result)) {
    $string = array(
      'comment'     => $child->location,
      'source'      => $child->source,
      'translation' => isset($child->translation) ? $child->translation : ''
    );
    if ($child->plid) {
      // Has a parent lid. Since we process in the order of plids,
      // we already have the parent in the array, so we can add the
      // lid to the next plural version to it. This builds a linked
      // list of plurals.
      $string['child'] = TRUE;
      $strings[$child->plid]['plural'] = $child->lid;
    }
    $strings[$child->lid] = $string;
  }
  return $strings;
}

/**
 * Generates the PO(T) file contents for given strings.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $strings
 *   Array of strings to export. See _locale_export_get_strings()
 *   on how it should be formatted.
 * @param $header
 *   The header portion to use for the output file. Defaults
 *   are provided for PO and POT files.
 */
function _locale_export_po_generate($language = NULL, $strings = array(), $header = NULL) {
  global $user;

  if (!isset($header)) {
    if (isset($language)) {
      $header = '# '. $language->name .' translation of '. variable_get('site_name', 'Drupal') ."\n";
      $header .= '# Generated by '. $user->name .' <'. $user->mail .">\n";
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
      $header .= "\"POT-Creation-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
      $header .= "\"PO-Revision-Date: ". date("Y-m-d H:iO") ."\\n\"\n";
      $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if ($language->formula && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=". $language->plurals ."; plural=". strtr($language->formula, array('$' => '')) .";\\n\"\n";
      }
    }
    else {
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
    }
  }

  $output = $header ."\n";

  foreach ($strings as $lid => $string) {
    // Only process non-children, children are output below their parent.
    if (!isset($string['child'])) {
      if ($string['comment']) {
        $output .= '#: '. $string['comment'] ."\n";
      }
      $output .= 'msgid '. _locale_export_string($string['source']);
      if (!empty($string['plural'])) {
        $plural = $string['plural'];
        $output .= 'msgid_plural '. _locale_export_string($strings[$plural]['source']);
        if (isset($language)) {
          $translation = $string['translation'];
          for ($i = 0; $i < $language->plurals; $i++) {
            $output .= 'msgstr['. $i .'] '. _locale_export_string($translation);
            if ($plural) {
              $translation = _locale_export_remove_plural($strings[$plural]['translation']);
              $plural = isset($strings[$plural]['plural']) ? $strings[$plural]['plural'] : 0;
            }
            else {
              $translation = '';
            }
          }
        }
        else {
          $output .= 'msgstr[0] ""'."\n";
          $output .= 'msgstr[1] ""'."\n";
        }
      }
      else {
        $output .= 'msgstr '. _locale_export_string($string['translation']);
      }
      $output .= "\n";
    }
  }
  return $output;
}

/**
 * Write a generated PO or POT file to the output.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $output
 *   The PO(T) file to output as a string. See _locale_export_generate_po()
 *   on how it can be generated.
 */
function _locale_export_po($language = NULL, $output = NULL) {
  // Log the export event.
  if (isset($language)) {
    $filename = $language->language .'.po';
    watchdog('locale', 'Exported %locale translation file: %filename.', array('%locale' => $language->name, '%filename' => $filename));
  }
  else {
    $filename = 'drupal.pot';
    watchdog('locale', 'Exported translation file: %filename.', array('%filename' => $filename));
  }
  // Download the file fo the client.
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");
  print $output;
  die();
}

/**
 * Print out a string on multiple lines
 */
function _locale_export_string($str) {
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
    $join = "SELECT s.source, s.location, s.lid, s.textgroup, t.translation, t.language FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid ";

    $arguments = array();
    $limit_language = FALSE;
    // Compute LIKE section
    switch ($query['translation']) {
      case 'translated':
        $where = "WHERE (t.translation LIKE '%%%s%%')";
        $orderby = "ORDER BY t.translation";
        $arguments[] = $query['string'];
        break;
      case 'untranslated':
        $where = "WHERE (s.source LIKE '%%%s%%' AND t.translation IS NULL)";
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
        $sql = "$join AND t.language = '%s' $where $grouplimit $orderby";
        array_unshift($arguments, $query['language']);
        // Don't show translation flags for other languages, we can't see them with this search.
        $limit_language = $query['language'];
    }

    $result = pager_query($sql, 50, 0, NULL, $arguments);

    $groups = module_invoke_all('locale', 'groups');
    $header = array(t('Text group'), t('String'), ($limit_language) ? t('Language') : t('Languages'), array('data' => t('Operations'), 'colspan' => '2'));
    $arr = array();
    while ($locale = db_fetch_object($result)) {
      $arr[$locale->lid]['group'] = $groups[$locale->textgroup];
      $arr[$locale->lid]['languages'][$locale->language] = $locale->translation;
      $arr[$locale->lid]['location'] = $locale->location;
      $arr[$locale->lid]['source'] = $locale->source;
    }
    $rows = array();
    foreach ($arr as $lid => $value) {
      $rows[] = array(
        $value['group'],
        array('data' => check_plain(truncate_utf8($value['source'], 150, FALSE, TRUE)) .'<br /><small>'. $value['location'] .'</small>'),
        array('data' => _locale_translate_language_list($value['languages'], $limit_language), 'align' => 'center'),
        array('data' => l(t('edit'), "admin/build/translate/edit/$lid"), 'class' => 'nowrap'),
        array('data' => l(t('delete'), "admin/build/translate/delete/$lid"), 'class' => 'nowrap'),
      );
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
 * Force the JavaScript translation file(s) to be refreshed.
 *
 * This function sets a refresh flag for a specified language, or all
 * languages except English, if none specified. JavaScript translation
 * files are rebuilt (with locale_update_js_files()) the next time a
 * request is served in that language.
 *
 * @param $langcode
 *   The language code for which the file needs to be refreshed.
 * @return
 *   New content of the 'javascript_parsed' variable.
 */
function _locale_invalidate_js($langcode = NULL) {
  $parsed = variable_get('javascript_parsed', array());

  if (empty($langcode)) {
    // Invalidate all languages.
    $languages = language_list();
    unset($languages['en']);
    foreach ($languages as $lcode => $data) {
      $parsed['refresh:'. $lcode] = 'waiting';
    }
  }
  else {
    // Invalidate single language.
    $parsed['refresh:'. $langcode] = 'waiting';
  }

  variable_set('javascript_parsed', $parsed);
  return $parsed;
}

/**
 * (Re-)Creates the JavaScript translation file for a language.
 *
 * @param $language
 *   The language, the translation file should be (re)created for.
 */
function _locale_rebuild_js($langcode = NULL) {
  if (!isset($langcode)) {
    global $language;
  }
  else {
    // Get information about the locale.
    $languages = language_list();
    $language = $languages[$langcode];
  }

  // Construct the array for JavaScript translations.
  // Only add strings with a translation to the translations array.
  $result = db_query("SELECT s.lid, s.source, t.translation FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid AND t.language = '%s' WHERE s.location LIKE '%%.js%%' AND s.textgroup = 'default'", $language->language);

  $translations = array();
  while ($data = db_fetch_object($result)) {
    $translations[$data->source] = $data->translation;
  }

  // Construct the JavaScript file, if there are translations.
  $data_hash = NULL;
  $data = $status = '';
  if (!empty($translations)) {

    $data = "Drupal.locale = { ";

    if (!empty($language->formula)) {
      $data .= "'pluralFormula': function(\$n) { return Number({$language->formula}); }, ";
    }

    $data .= "'strings': ". drupal_to_js($translations) ." };";
    $data_hash = md5($data);
  }

  // Construct the filepath where JS translation files are stored.
  // There is (on purpose) no front end to edit that variable.
  $dir = file_create_path(variable_get('locale_js_directory', 'languages'));

  // Delete old file, if we have no translations anymore, or a different file to be saved.
  $changed_hash = $language->javascript != $data_hash;
  if (!empty($language->javascript) && (!$data || $changed_hash)) {
    file_delete(file_create_path($dir .'/'. $language->language .'_'. $language->javascript .'.js'));
    $language->javascript = '';
    $status = 'deleted';
  }

  // Only create a new file if the content has changed or the original file got
  // lost.
  $dest = $dir .'/'. $language->language .'_'. $data_hash .'.js';
  if ($data && ($changed_hash || !file_exists($dest))) {
    // Ensure that the directory exists and is writable, if possible.
    file_check_directory($dir, TRUE);

    // Save the file.
    if (file_save_data($data, $dest)) {
      $language->javascript = $data_hash;
      // If we deleted a previous version of the file and we replace it with a
      // new one we have an update.
      if ($status == 'deleted') {
        $status = 'updated';
      }
      // If the file did not exist previously and the data has changed we have
      // a fresh creation.
      elseif ($changed_hash) {
        $status = 'created';
      }
      // If the data hash is unchanged the translation was lost and has to be
      // rebuilt.
      else {
        $status = 'rebuilt';
      }
    }
    else {
      $language->javascript = '';
      $status = 'error';
    }
  }

  // Save the new JavaScript hash (or an empty value if the file just got
  // deleted). Act only if some operation was executed that changed the hash
  // code.
  if ($status && $changed_hash) {
    db_query("UPDATE {languages} SET javascript = '%s' WHERE language = '%s'", $language->javascript, $language->language);

    // Update the default language variable if the default language has been altered.
    // This is necessary to keep the variable consistent with the database
    // version of the language and to prevent checking against an outdated hash.
    $default_langcode = language_default('language');
    if ($default_langcode == $language->language) {
      $default = db_fetch_object(db_query("SELECT * FROM {languages} WHERE language = '%s'", $default_langcode));
      variable_set('language_default', $default);
    }
  }

  // Log the operation and return success flag.
  switch ($status) {
    case 'updated':
      watchdog('locale', 'Updated JavaScript translation file for the language %language.', array('%language' => t($language->name)));
      return TRUE;
    case 'rebuilt':
      watchdog('locale', 'JavaScript translation file %file.js was lost.', array('%file' => $language->javascript), WATCHDOG_WARNING);
      // Proceed to the 'created' case as the JavaScript translation file has
      // been created again.
    case 'created':
      watchdog('locale', 'Created JavaScript translation file for the language %language.', array('%language' => t($language->name)));
      return TRUE;
    case 'deleted':
      watchdog('locale', 'Removed JavaScript translation file for the language %language, because no translations currently exist for that language.', array('%language' => t($language->name)));
      return TRUE;
    case 'error':
      watchdog('locale', 'An error occurred during creation of the JavaScript translation file for the language %language.', array('%language' => t($language->name)), WATCHDOG_ERROR);
      return FALSE;
    default:
      // No operation needed.
      return TRUE;
  }
}

/**
 * List languages in search result table
 */
function _locale_translate_language_list($translation, $limit_language) {
  // Add CSS
  drupal_add_css(drupal_get_path('module', 'locale') .'/locale.css', 'module', 'all', FALSE);

  $languages = language_list();
  unset($languages['en']);
  $output = '';
  foreach ($languages as $langcode => $language) {
    if (!$limit_language || $limit_language == $langcode) {
      $output .= (!empty($translation[$langcode])) ? $langcode .' ' : "<em class=\"locale-untranslated\">$langcode</em> ";
    }
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
    if (count($value) > 1) {
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
    "ar" => array("Arabic", /* Left-to-right marker "‭" */ "العربية", LANGUAGE_RTL),
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
    "fa" => array("Persian", /* Left-to-right marker "‭" */ "فارسی", LANGUAGE_RTL),
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
    "he" => array("Hebrew", /* Left-to-right marker "‭" */ "עברית", LANGUAGE_RTL),
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
    "lt" => array("Lithuanian", "Lietuvių"),
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
    "ps" => array("Pashto", /* Left-to-right marker "‭" */ "پښتو", LANGUAGE_RTL),
    "pt-pt" => array("Portuguese, Portugal", "Português"),
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
    "si" => array("Sinhala", "සිංහල"),
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
    "ur" => array("Urdu", /* Left-to-right marker "‭" */ "اردو", LANGUAGE_RTL),
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

/**
 * @defgroup locale-autoimport Automatic interface translation import
 * @{
 */

/**
 * Prepare a batch to import translations for all enabled
 * modules in a given language.
 *
 * @param $langcode
 *   Language code to import translations for.
 * @param $finished
 *   Optional finished callback for the batch.
 * @param $skip
 *   Array of component names to skip. Used in the installer for the
 *   second pass import, when most components are already imported.
 * @return
 *   A batch structure or FALSE if no files found.
 */
function locale_batch_by_language($langcode, $finished = NULL, $skip = array()) {
  // Collect all files to import for all enabled modules and themes.
  $files = array();
  $components = array();
  $query = "SELECT name, filename FROM {system} WHERE status = 1";
  if (count($skip)) {
    $query .= " AND name NOT IN (". db_placeholders($skip, 'varchar') .")";
  }
  $result = db_query($query, $skip);
  while ($component = db_fetch_object($result)) {
    // Collect all files for all components, names as $langcode.po or
    // with names ending with $langcode.po. This allows for filenames
    // like node-module.de.po to let translators use small files and
    // be able to import in smaller chunks.
    $files = array_merge($files, file_scan_directory(dirname($component->filename) .'/translations', '(^|\.)'. $langcode .'\.po$', array('.', '..', 'CVS'), 0, FALSE));
    $components[] = $component->name;
  }

  return _locale_batch_build($files, $finished, $components);
}

/**
 * Prepare a batch to run when installing modules or enabling themes.
 * This batch will import translations for the newly added components
 * in all the languages already set up on the site.
 *
 * @param $components
 *   An array of component (theme and/or module) names to import
 *   translations for.
 * @param $finished
 *   Optional finished callback for the batch.
 */
function locale_batch_by_component($components, $finished = '_locale_batch_system_finished') {
  $files = array();
  $languages = language_list('enabled');
  unset($languages[1]['en']);
  if (count($languages[1])) {
    $language_list = join('|', array_keys($languages[1]));
    // Collect all files to import for all $components.
    $result = db_query("SELECT name, filename FROM {system} WHERE status = 1");
    while ($component = db_fetch_object($result)) {
      if (in_array($component->name, $components)) {
        // Collect all files for this component in all enabled languages, named
        // as $langcode.po or with names ending with $langcode.po. This allows
        // for filenames like node-module.de.po to let translators use small
        // files and be able to import in smaller chunks.
        $files = array_merge($files, file_scan_directory(dirname($component->filename) .'/translations', '(^|\.)('. $language_list .')\.po$', array('.', '..', 'CVS'), 0, FALSE));
      }
    }
    return _locale_batch_build($files, $finished);
  }
  return FALSE;
}

/**
 * Build a locale batch from an array of files.
 *
 * @param $files
 *   Array of files to import
 * @param $finished
 *   Optional finished callback for the batch.
 * @param $components
 *   Optional list of component names the batch covers. Used in the installer.
 * @return
 *   A batch structure
 */
function _locale_batch_build($files, $finished = NULL, $components = array()) {
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call _locale_batch_import for every batch operation.
      $operations[] = array('_locale_batch_import', array($file->filename));
    }
    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => './includes/locale.inc',
      // This is not a batch API construct, but data passed along to the
      // installer, so we know what did we import already.
      '#components'   => $components,
    );
    if (isset($finished)) {
      $batch['finished'] = $finished;
    }
    return $batch;
  }
  return FALSE;
}

/**
 * Perform interface translation import as a batch step.
 *
 * @param $filepath
 *   Path to a file to import.
 * @param $results
 *   Contains a list of files imported.
 */
function _locale_batch_import($filepath, &$context) {
  // The filename is either {langcode}.po or {prefix}.{langcode}.po, so
  // we can extract the language code to use for the import from the end.
  if (preg_match('!(/|\.)([^\./]+)\.po$!', $filepath, $langcode)) {
    $file = (object) array('filename' => basename($filepath), 'filepath' => $filepath);
    _locale_import_read_po('db-store', $file, LOCALE_IMPORT_KEEP, $langcode[2]);
    $context['results'][] = $filepath;
  }
}

/**
 * Finished callback of system page locale import batch.
 * Inform the user of translation files imported.
 */
function _locale_batch_system_finished($success, $results) {
  if ($success) {
    drupal_set_message(format_plural(count($results), 'One translation file imported for the newly installed modules.', '@count translation files imported for the newly installed modules.'));
  }
}

/**
 * Finished callback of language addition locale import batch.
 * Inform the user of translation files imported.
 */
function _locale_batch_language_finished($success, $results) {
  if ($success) {
    drupal_set_message(format_plural(count($results), 'One translation file imported for the enabled modules.', '@count translation files imported for the enabled modules.'));
  }
}

/**
 * @} End of "locale-autoimport"
 */
