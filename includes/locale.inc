<?php

/**
 * @file
 * Admin-related functions for locale.module.
 */

// ---------------------------------------------------------------------------------
// Language addition functionality (administration only)

/**
 * Helper function to add a language
 */
function _locale_add_language($code, $name, $onlylanguage = TRUE) {
  db_query("INSERT INTO {locales_meta} (locale, name) VALUES ('%s','%s')", $code, $name);
  $result = db_query("SELECT lid FROM {locales_source}");
  while ($string = db_fetch_object($result)) {
    db_query("INSERT INTO {locales_target} (lid, locale, translation) VALUES (%d,'%s', '')", $string->lid, $code);
  }

  // If only the language was added, and not a PO file import triggered
  // the language addition, we need to inform the user on how to start
  // a translation
  if ($onlylanguage) {
    drupal_set_message(t('The language %locale has been created and can now be used to import a translation. More information is available in the <a href="@locale-help">help screen</a>.', array('%locale' => t($name), '@locale-help' => url('admin/help/locale'))));
  }
  else {
    drupal_set_message(t('The language %locale has been created.', array('%locale' => t($name))));
  }

  watchdog('locale', t('The %language language (%locale) has been created.', array('%language' => $name, '%locale' => $code)));
}

/**
 * User interface for the language management screen.
 */
function _locale_admin_manage_screen() {
  $languages = locale_supported_languages(TRUE, TRUE);

  $options = array();
  $form['name'] = array('#tree' => TRUE);
  foreach ($languages['name'] as $key => $lang) {
    // Language code should contain no markup, but is emitted
    // by radio and checkbox options.
    $key = check_plain($key);
    $options[$key] = '';
    $status = db_fetch_object(db_query("SELECT isdefault, enabled FROM {locales_meta} WHERE locale = '%s'", $key));
    if ($status->enabled) {
      $enabled[] = $key;
    }
    if ($status->isdefault) {
      $isdefault = $key;
    }
    if ($key == 'en') {
      $form['name']['en'] = array('#value' => check_plain($lang));
    }
    else {
      $original = db_fetch_object(db_query("SELECT COUNT(*) AS strings FROM {locales_source}"));
      $translation = db_fetch_object(db_query("SELECT COUNT(*) AS translation FROM {locales_target} WHERE locale = '%s' AND translation != ''", $key));

      $ratio = ($original->strings > 0 && $translation->translation > 0) ? round(($translation->translation/$original->strings)*100., 2) : 0;

      $form['name'][$key] = array('#type' => 'textfield',
        '#default_value' => $lang,
        '#size' => 15,
        '#maxlength' => 64,
      );
      $form['translation'][$key] = array('#value' => "$translation->translation/$original->strings ($ratio%)");
    }
  }
  $form['enabled'] = array('#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $enabled,
  );
  $form['site_default'] = array('#type' => 'radios',
    '#options' => $options,
    '#default_value' => $isdefault,
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  $form['#base'] = 'locale_admin_manage_screen';

  return $form;
}

/**
 * Theme the locale admin manager form.
 */
function theme_locale_admin_manage_screen($form) {
  foreach ($form['name'] as $key => $element) {
    // Do not take form control structures.
    if (is_array($element) && element_child($key)) {
      $rows[] = array(check_plain($key), drupal_render($form['name'][$key]), drupal_render($form['enabled'][$key]), drupal_render($form['site_default'][$key]), ($key != 'en' ? drupal_render($form['translation'][$key]) : t('n/a')), ($key != 'en' ? l(t('delete'), 'admin/settings/locale/language/delete/'. $key) : ''));
    }
  }
  $header = array(array('data' => t('Code')), array('data' => t('English name')), array('data' => t('Enabled')), array('data' => t('Default')), array('data' => t('Translated')), array('data' => t('Operations')));
  $output = theme('table', $header, $rows);
  $output .= drupal_render($form);

  return $output;
}

function _locale_admin_manage_screen_validate($form_id, $form_values) {
  foreach ($form_values['name'] as $key => $value) {
    if (preg_match('/["<>\']/', $value)) {
      form_set_error('name][' . $key, t('The characters &lt;, &gt;, " and \' are not allowed in the language name in English field.'));
    }
  }
}

/**
 * Process locale admin manager form submissions.
 */
function _locale_admin_manage_screen_submit($form_id, $form_values) {
  // Save changes to existing languages.
  $languages = locale_supported_languages(FALSE, TRUE);
  foreach ($languages['name'] as $key => $value) {
    if ($form_values['site_default'] == $key) {
      $form_values['enabled'][$key] = 1; // autoenable the default language
    }
    $enabled = $form_values['enabled'][$key] ? 1 : 0;
    if ($key == 'en') {
      // Disallow name change for English locale.
      db_query("UPDATE {locales_meta} SET isdefault = %d, enabled = %d WHERE locale = 'en'", ($form_values['site_default'] == $key), $enabled);
    }
    else {
      db_query("UPDATE {locales_meta} SET name = '%s', isdefault = %d, enabled = %d WHERE locale = '%s'", $form_values['name'][$key], ($form_values['site_default'] == $key), $enabled, $key);
    }
  }
  drupal_set_message(t('Configuration saved.'));

  // Changing the locale settings impacts the interface:
  cache_clear_all('*', 'cache_menu', TRUE);
  cache_clear_all('*', 'cache_page', TRUE);

  return 'admin/settings/locale/language/overview';
}

function locale_add_language_form() {
  $isocodes = _locale_prepare_iso_list();
  $form = array();
  $form['language list'] = array('#type' => 'fieldset',
    '#title' => t('Language list'),
    '#collapsible' => TRUE,
  );
  $form['language list']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#default_value' => key($isocodes),
    '#options' => $isocodes,
    '#description' => t('Select your language here, or add it below, if you are unable to find it.'),
  );
  $form['language list']['submit'] = array('#type' => 'submit', '#value' => t('Add language'));
  return $form;
}

function locale_custom_language_form() {
  $form = array();
  $form['custom language'] = array('#type' => 'fieldset',
    '#title' => t('Custom language'),
    '#collapsible' => TRUE,
  );
  $form['custom language']['langcode'] = array('#type' => 'textfield',
    '#title' => t('Language code'),
    '#size' => 12,
    '#maxlength' => 60,
    '#required' => TRUE,
    '#description' => t('Commonly this is an <a href="@iso-codes">ISO 639 language code</a> with an optional country code for regional variants. Examples include "en", "en-US" and "zh-cn".', array('@iso-codes' => 'http://www.w3.org/WAI/ER/IG/ert/iso639.htm')),
  );
  $form['custom language']['langname'] = array('#type' => 'textfield',
    '#title' => t('Language name in English'),
    '#maxlength' => 64,
    '#required' => TRUE,
    '#description' => t('Name of the language. Will be available for translation in all languages.'),
  );
  $form['custom language']['submit'] = array('#type' => 'submit', '#value' => t('Add custom language'));
  // Use the validation and submit functions of the add language form.
  $form['#base'] = 'locale_add_language_form';
  return $form;
}

/**
 * User interface for the language addition screen.
 */
function _locale_admin_manage_add_screen() {
  $output = drupal_get_form('locale_add_language_form');
  $output .= drupal_get_form('locale_custom_language_form');
  return $output;
}

/**
 * Validate the language addition form.
 */
function locale_add_language_form_validate($form_id, $form_values) {
  if ($duplicate = db_num_rows(db_query("SELECT locale FROM {locales_meta} WHERE locale = '%s'", $form_values['langcode'])) != 0) {
    form_set_error(t('The language %language (%code) already exists.', array('%language' => $form_values['langname'], '%code' => $form_values['langcode'])));
  }

  // If we are adding a non-custom language, check for a valid langcode.
  if (!isset($form_values['langname'])) {
    $isocodes = _locale_get_iso639_list();
    if (!isset($isocodes[$form_values['langcode']])) {
      form_set_error('langcode', t('Invalid language code.'));
    }
  }
  // Otherwise, check for invlaid characters
  else {
    if (preg_match('/["<>\']/', $form_values['langcode'])) {
      form_set_error('langcode', t('The characters &lt;, &gt;, " and \' are not allowed in the language code field.'));
    }
    if (preg_match('/["<>\']/', $form_values['langname'])) {
      form_set_error('langname', t('The characters &lt;, &gt;, " and \' are not allowed in the language name in English field.'));
    }
  }
}

/**
 * Process the language addition form submission.
 */
function locale_add_language_form_submit($form_id, $form_values) {
  if (isset($form_values['langname'])) {
    // Custom language form.
    _locale_add_language($form_values['langcode'], $form_values['langname']);
  }
  else {
    $isocodes = _locale_get_iso639_list();
    _locale_add_language($form_values['langcode'], $isocodes[$form_values['langcode']][0]);
  }

  return 'admin/settings/locale';
}

/**
 * User interface for the translation import screen.
 */
function _locale_admin_import() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $languages = array_map('t', $languages['name']);
  unset($languages['en']);

  if (!count($languages)) {
    $languages = _locale_prepare_iso_list();
  }
  else {
    $languages = array(
      t('Already added languages') => $languages,
      t('Languages not yet added') => _locale_prepare_iso_list()
    );
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
    '#description' => t('Choose the language you want to add strings into. If you choose a language which is not yet set up, then it will be added.'),
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
function _locale_admin_import_submit($form_id, $form_values) {
  // Add language, if not yet supported
  $languages = locale_supported_languages(TRUE, TRUE);
  if (!isset($languages['name'][$form_values['langcode']])) {
    $isocodes = _locale_get_iso639_list();
    _locale_add_language($form_values['langcode'], $isocodes[$form_values['langcode']][0], FALSE);
  }

  // Now import strings into the language
  $file = file_check_upload('file');
  if ($ret = _locale_import_po($file, $form_values['langcode'], $form_values['mode']) == FALSE) {
    $message = t('The translation import of %filename failed.', array('%filename' => $file->filename));
    drupal_set_message($message, 'error');
    watchdog('locale', $message, WATCHDOG_ERROR);
  }

  return 'admin/settings/locale';
}

function _locale_export_po_form($languages) {
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export translation'),
    '#collapsible' => TRUE,
  );
  $form['export']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $languages,
    '#description' => t('Select the language you would like to export in gettext Portable Object (.po) format.'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  return $form;
}

function _locale_export_pot_form() {
  // Complete template export of the strings
  $form['export'] = array('#type' => 'fieldset',
    '#title' => t('Export template'),
    '#collapsible' => TRUE,
    '#description' => t('Generate a gettext Portable Object Template (.pot) file with all the interface strings from the Drupal locale database.'),
  );
  $form['export']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  $form['#base'] = '_locale_export_po_form';
  return $form;
}

/**
 * User interface for the translation export screen
 */
function _locale_admin_export_screen() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $languages = array_map('t', $languages['name']);
  unset($languages['en']);

  $output = '';
  // Offer language specific export if any language is set up
  if (count($languages)) {
    $output = drupal_get_form('_locale_export_po_form', $languages);
  }

  $output .= drupal_get_form('_locale_export_pot_form');

  return $output;
}

/**
 * Process a locale export form submissions.
 */
function _locale_export_po_form_submit($form_id, $form_values) {
  _locale_export_po($form_values['langcode']);
}

/**
 * User interface for the string search screen
 */
function _locale_string_seek_form() {
  // Get *all* languages set up
  $languages = locale_supported_languages(FALSE, TRUE);
  unset($languages['name']['en']);
  // Sanitize the values to be used in radios.
  $languages_name = array();
  foreach ($languages['name'] as $key => $value) {
    $languages_name[check_plain($key)] = check_plain($value);
  }
  $languages['name'] = $languages_name;
  asort($languages['name']);

  // Present edit form preserving previous user settings
  $query = _locale_string_seek_query();
  $form = array();
  $form['search'] = array('#type' => 'fieldset',
    '#title' => t('Search'),
  );
  $form['search']['string'] = array('#type' => 'textfield',
    '#title' => t('Strings to search for'),
    '#default_value' => $query->string,
    '#size' => 30,
    '#maxlength' => 30,
    '#description' => t('Leave blank to show all strings. The search is case sensitive.'),
  );
  $form['search']['language'] = array('#type' => 'radios',
    '#title' => t('Language'),
    '#default_value' => ($query->language ? $query->language : 'all'),
    '#options' => array_merge(array('all' => t('All languages'), 'en' => t('English (provided by Drupal)')), $languages['name']),
  );
  $form['search']['searchin'] = array('#type' => 'radios',
    '#title' => t('Search in'),
    '#default_value' => ($query->searchin ? $query->searchin : 'all'),
    '#options' => array('all' => t('All strings in that language'), 'translated' => t('Only translated strings'), 'untranslated' => t('Only untranslated strings')),
  );
  $form['search']['submit'] = array('#type' => 'submit', '#value' => t('Search'));
  $form['#redirect'] = FALSE;

  return $form;
}

/**
 * User interface for string editing.
 */
function _locale_string_edit($lid) {
  $languages = locale_supported_languages(FALSE, TRUE);
  unset($languages['name']['en']);

  $result = db_query('SELECT DISTINCT s.source, t.translation, t.locale FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE s.lid = %d', $lid);
  $form = array();
  $form['translations'] = array('#tree' => TRUE);
  while ($translation = db_fetch_object($result)) {
    $orig = $translation->source;

    // Approximate the number of rows in a textfield with a maximum of 10.
    $rows = min(ceil(str_word_count($orig) / 12), 10);

    $form['translations'][$translation->locale] = array(
      '#type' => 'textarea',
      '#title' => $languages['name'][$translation->locale],
      '#default_value' => $translation->translation,
      '#rows' => $rows,
    );
    unset($languages['name'][$translation->locale]);
  }

  // Handle erroneous lid.
  if (!isset($orig)){
    drupal_set_message(t('String not found.'));
    drupal_goto('admin/settings/locale/string/search');
  }

  // Add original text. Assign negative weight so that it floats to the top.
  $form['item'] = array('#type' => 'item',
    '#title' => t('Original text'),
    '#value' => check_plain(wordwrap($orig, 0)),
    '#weight' => -1,
  );

  foreach ($languages['name'] as $key => $lang) {
    $form['translations'][$key] = array(
      '#type' => 'textarea',
      '#title' => $lang,
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
function _locale_string_edit_submit($form_id, $form_values) {
  $lid = $form_values['lid'];
  foreach ($form_values['translations'] as $key => $value) {
    $trans = db_fetch_object(db_query("SELECT translation FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $key));
    if (isset($trans->translation)) {
      db_query("UPDATE {locales_target} SET translation = '%s' WHERE lid = %d AND locale = '%s'", $value, $lid, $key);
    }
    else {
      db_query("INSERT INTO {locales_target} (lid, translation, locale) VALUES (%d, '%s', '%s')", $lid, $value, $key);
    }
  }
  drupal_set_message(t('The string has been saved.'));

  // Refresh the locale cache.
  locale_refresh_cache();
  // Rebuild the menu, strings may have changed.
  menu_rebuild();

  return 'admin/settings/locale/string/search';
}

/**
 * Delete a language string.
 */
function _locale_string_delete($lid) {
  db_query('DELETE FROM {locales_source} WHERE lid = %d', $lid);
  db_query('DELETE FROM {locales_target} WHERE lid = %d', $lid);
  locale_refresh_cache();
  drupal_set_message(t('The string has been removed.'));

  drupal_goto('admin/settings/locale/string/search');
}

/**
 * Parses Gettext Portable Object file information and inserts into database
 *
 * @param $file
 *   Drupal file object corresponding to the PO file to import
 * @param $lang
 *   Language code
 * @param $mode
 *   Should existing translations be replaced ('overwrite' or 'keep')
 */
function _locale_import_po($file, $lang, $mode) {
  // If not in 'safe mode', increase the maximum execution time:
  if (!ini_get('safe_mode')) {
    set_time_limit(240);
  }

  // Check if we have the language already in the database
  if (!db_fetch_object(db_query("SELECT locale FROM {locales_meta} WHERE locale = '%s'", $lang))) {
    drupal_set_message(t('The language selected for import is not supported.'), 'error');
    return FALSE;
  }

  // Get strings from file (returns on failure after a partial import, or on success)
  $status = _locale_import_read_po('db-store', $file, $mode, $lang);
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
  watchdog('locale', t('Imported %file into %locale: %number new strings added and %update updated.', array('%file' => $file->filename, '%locale' => $lang, '%number' => $additions, '%update' => $updates)));
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
 */
function _locale_import_read_po($op, $file, $mode = NULL, $lang = NULL) {

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
 */
function _locale_import_one_string($op, $value = NULL, $mode = NULL, $lang = NULL, $file = NULL) {
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
        if ($hdr["Plural-Forms"] && $p = _locale_import_parse_plural_forms($hdr["Plural-Forms"], $file->filename)) {
          list($nplurals, $plural) = $p;
          db_query("UPDATE {locales_meta} SET plurals = %d, formula = '%s' WHERE locale = '%s'", $nplurals, $plural, $lang);
        }
        else {
          db_query("UPDATE {locales_meta} SET plurals = %d, formula = '%s' WHERE locale = '%s'", 0, '', $lang);
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
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english[$key]));
            if (!empty($loc->lid)) { // a string exists
              $lid = $loc->lid;
              // update location field
              db_query("UPDATE {locales_source} SET location = '%s' WHERE lid = %d", $comments, $lid);
              $trans2 = db_fetch_object(db_query("SELECT lid, translation, plid, plural FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $lang));
              if (!$trans2->lid) { // no translation in current language
                db_query("INSERT INTO {locales_target} (lid, locale, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
                $additions++;
              } // translation exists
              else if ($mode == 'overwrite' || $trans2->translation == '') {
                db_query("UPDATE {locales_target} SET translation = '%s', plid = %d, plural = %d WHERE locale = '%s' AND lid = %d", $trans, $plid, $key, $lang, $lid);
                if ($trans2->translation == '') {
                  $additions++;
                }
                else {
                  $updates++;
                }
              }
            }
            else { // no string
              db_query("INSERT INTO {locales_source} (location, source) VALUES ('%s', '%s')", $comments, $english[$key]);
              $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english[$key]));
              $lid = $loc->lid;
              db_query("INSERT INTO {locales_target} (lid, locale, translation, plid, plural) VALUES (%d, '%s', '%s', %d, %d)", $lid, $lang, $trans, $plid, $key);
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
          $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english));
          if (!empty($loc->lid)) { // a string exists
            $lid = $loc->lid;
            // update location field
            db_query("UPDATE {locales_source} SET location = '%s' WHERE source = '%s'", $comments, $english);
            $trans = db_fetch_object(db_query("SELECT lid, translation FROM {locales_target} WHERE lid = %d AND locale = '%s'", $lid, $lang));
            if (!$trans->lid) { // no translation in current language
              db_query("INSERT INTO {locales_target} (lid, locale, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
              $additions++;
            } // translation exists
            else if ($mode == 'overwrite') { //overwrite in any case
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE locale = '%s' AND lid = %d", $translation, $lang, $lid);
              if ($trans->translation == '') {
                $additions++;
              }
              else {
                $updates++;
              }
            } // overwrite if empty string
            else if ($trans->translation == '') {
              db_query("UPDATE {locales_target} SET translation = '%s' WHERE locale = '%s' AND lid = %d", $translation, $lang, $lid);
              $additions++;
            }
          }
          else { // no string
            db_query("INSERT INTO {locales_source} (location, source) VALUES ('%s', '%s')", $comments, $english);
            $loc = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE source = '%s'", $english));
            $lid = $loc->lid;
            db_query("INSERT INTO {locales_target} (lid, locale, translation) VALUES (%d, '%s', '%s')", $lid, $lang, $translation);
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
 * @param $header
 *   A string containing the complete header
 * @return
 *   An associative array of key-value pairs
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
      while (($topop != NULL) && ($topop != "(")) {
        $elstk[] = $topop;
        $topop = array_pop($opstk);
      }
    }
    elseif (!empty($prec[$ctok])) {
      // If it's an operator, then pop from $oparr into $elarr until the
      // precedence in $oparr is less than current, then push into $oparr
      $topop = array_pop($opstk);
      while (($topop != NULL) && ($prec[$topop] >= $prec[$ctok]) && !(($prec[$topop] == $prec[$ctok]) && $rasc[$topop] && $rasc[$ctok])) {
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
      if ($prec[$op]) {
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
  return substr($comm, 0, -2);
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
 * Exports a Portable Object (Template) file for a language
 *
 * @param $language Selects a language to generate the output for
 */
function _locale_export_po($language) {
  global $user;

  // Get language specific strings, or all strings
  if ($language) {
    $meta = db_fetch_object(db_query("SELECT * FROM {locales_meta} WHERE locale = '%s'", $language));
    $result = db_query("SELECT s.lid, s.source, s.location, t.translation, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid WHERE t.locale = '%s' ORDER BY t.plid, t.plural", $language);
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.location, t.plid, t.plural FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ORDER BY t.plid, t.plural");
  }

  // Build array out of the database results
  $parent = array();
  while ($child = db_fetch_object($result)) {
    if ($child->source != '') {
      $parent[$child->lid]['comment'] = $child->location;
      $parent[$child->lid]['msgid'] = $child->source;
      $parent[$child->lid]['translation'] = $child->translation;
      if ($child->plid) {
        $parent[$child->lid]['child'] = 1;
        $parent[$child->plid]['plural'] = $child->lid;
      }
    }
  }

  // Generating Portable Object file for a language
  if ($language) {
    $filename = $language .'.po';
    $header .= "# $meta->name translation of ". variable_get('site_name', 'Drupal') ."\n";
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
    watchdog('locale', t('Exported %locale translation file: %filename.', array('%locale' => $meta->name, '%filename' => $filename)));
  }

  // Generating Portable Object Template
  else {
    $filename = 'drupal.pot';
    $header .= "# LANGUAGE translation of PROJECT\n";
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
    watchdog('locale', t('Exported translation file: %filename.', array('%filename' => $filename)));
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
      if ($plural = $message['plural']) {
        print 'msgid_plural '. _locale_export_print($parent[$plural]['msgid']);
        if ($language) {
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
          print 'msgstr[0] ""'. "\n";
          print 'msgstr[1] ""'. "\n";
        }
      }
      else {
        if ($language) {
          print 'msgstr '. _locale_export_print($message['translation']);
        }
        else {
          print 'msgstr ""'. "\n";
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

  if (count($parts) > 1) {
    return "\"\"\n\"". implode("\"\n\"", $parts) ."\"\n";
  }
  else {
    return "\"$parts[0]\"\n";
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
      $ret[] = $cur . " ";
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
 * List languages in search result table
 */
function _locale_string_language_list($translation) {
  // Add CSS
  drupal_add_css(drupal_get_path('module', 'locale') .'/locale.css', 'module', 'all', FALSE);

  $languages = locale_supported_languages(FALSE, TRUE);
  unset($languages['name']['en']);
  $output = '';
  foreach ($languages['name'] as $key => $value) {
    if (isset($translation[$key])) {
      $output .= ($translation[$key] != '') ? $key .' ' : "<em class=\"locale-untranslated\">$key</em> ";
    }
  }

  return $output;
}

/**
 * Build object out of search criteria specified in request variables
 */
function _locale_string_seek_query() {
  static $query;

  if (!isset($query)) {
    $fields = array('string', 'language', 'searchin');
    $query = new stdClass();
    if (is_array($_REQUEST['edit'])) {
      foreach ($_REQUEST['edit'] as $key => $value) {
        if (!empty($value) && in_array($key, $fields)) {
          $query->$key = $value;
        }
      }
    }
    else {
      foreach ($_REQUEST as $key => $value) {
        if (!empty($value) && in_array($key, $fields)) {
          $query->$key = strpos(',', $value) ? explode(',', $value) : $value;
        }
      }
    }
  }
  return $query;
}

/**
 * Perform a string search and display results in a table
 */
function _locale_string_seek() {
  // We have at least one criterion to match
  if ($query = _locale_string_seek_query()) {
    $join = "SELECT s.source, s.location, s.lid, t.translation, t.locale FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid ";

    $arguments = array();
    // Compute LIKE section
    switch ($query->searchin) {
      case 'translated':
        $where = "WHERE (t.translation LIKE '%%%s%%' AND t.translation != '')";
        $orderby = "ORDER BY t.translation";
        $arguments[] = $query->string;
        break;
      case 'untranslated':
        $where = "WHERE (s.source LIKE '%%%s%%' AND t.translation = '')";
        $orderby = "ORDER BY s.source";
        $arguments[] = $query->string;
        break;
      case 'all' :
      default:
        $where = "WHERE (s.source LIKE '%%%s%%' OR t.translation LIKE '%%%s%%')";
        $orderby = '';
        $arguments[] = $query->string;
        $arguments[] = $query->string;
        break;
    }

    switch ($query->language) {
      // Force search in source strings
      case "en":
        $sql = $join ." WHERE s.source LIKE '%%%s%%' ORDER BY s.source";
        $arguments = array($query->string); // $where is not used, discard its arguments
        break;
      // Search in all languages
      case "all":
        $sql = "$join $where $orderby";
        break;
      // Some different language
      default:
        $sql = "$join $where AND t.locale = '%s' $orderby";
        $arguments[] = $query->language;
    }

    $result = pager_query($sql, 50, 0, NULL, $arguments);

    $header = array(t('String'), t('Locales'), array('data' => t('Operations'), 'colspan' => '2'));
    $arr = array();
    while ($locale = db_fetch_object($result)) {
      $arr[$locale->lid]['locales'][$locale->locale] = $locale->translation;
      $arr[$locale->lid]['location'] = $locale->location;
      $arr[$locale->lid]['source'] = $locale->source;
    }
    foreach ($arr as $lid => $value) {
      $rows[] = array(array('data' => check_plain(truncate_utf8($value['source'], 150, FALSE, TRUE)) .'<br /><small>'. $value['location'] .'</small>'), array('data' => _locale_string_language_list($value['locales']), 'align' => 'center'), array('data' => l(t('edit'), "admin/settings/locale/string/edit/$lid"), 'class' => 'nowrap'), array('data' => l(t('delete'), "admin/settings/locale/string/delete/$lid"), 'class' => 'nowrap'));
    }

    $request = array();
    if (count($query)) {
      foreach ($query as $key => $value) {
        $request[$key] = (is_array($value)) ? implode(',', $value) : $value;
      }
    }

    if (count($rows)) {
      $output .= theme('table', $header, $rows);
    }
    if ($pager = theme('pager', NULL, 50, 0, $request)) {
      $output .= $pager;
    }
  }

  return $output;
}

// ---------------------------------------------------------------------------------
// List of some of the most common languages (administration only)

/**
 * Prepares the language code list for a select form item with only the unsupported ones
 */
function _locale_prepare_iso_list() {
  $languages = locale_supported_languages(FALSE, TRUE);
  $isocodes = _locale_get_iso639_list();
  foreach ($isocodes as $key => $value) {
    if (isset($languages['name'][$key])) {
      unset($isocodes[$key]);
      continue;
    }
    if (count($value) == 2) {
      $tname = t($value[0]);
      $isocodes[$key] = ($tname == $value[1]) ? $tname : "$tname ($value[1])";
    }
    else {
      $isocodes[$key] = t($value[0]);
    }
  }
  asort($isocodes);
  return $isocodes;
}

/**
 * Some of the common languages with their English and native names
 *
 * Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
 */
function _locale_get_iso639_list() {
  return array(
    "aa" => array("Afar"),
    "ab" => array("Abkhazian", "аҧсуа бызшәа"),
    "ae" => array("Avestan"),
    "af" => array("Afrikaans"),
    "ak" => array("Akan"),
    "am" => array("Amharic", "አማርኛ"),
    "ar" => array("Arabic", "العربية"),
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
    "fa" => array("Persian", "فارسی"),
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
    "he" => array("Hebrew", "עברית"),
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
    "ps" => array("Pashto", "پښتو"),
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
    "ur" => array("Urdu", "اردو"),
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
