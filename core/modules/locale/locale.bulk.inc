<?php

/**
 * @file
 * Mass import-export and batch import functionality for Gettext .po files.
 */

include_once DRUPAL_ROOT . '/core/includes/gettext.inc';

/**
 * User interface for the translation import screen.
 */
function locale_translate_import_form($form, &$form_state) {
  drupal_static_reset('language_list');
  $languages = language_list(TRUE);

  // Initialize a language list to the ones available, including English if we
  // are to translate Drupal to English as well.
  $existing_languages = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $existing_languages[$langcode] = $language->name;
    }
  }

  // If we have no languages available, present the list of predefined languages
  // only. If we do have already added languages, set up two option groups with
  // the list of existing and then predefined languages.
  form_load_include($form_state, 'inc', 'language', 'language.admin');
  if (empty($existing_languages)) {
    $language_options = language_admin_predefined_list();
    $default = key($language_options);
  }
  else {
    $default = key($existing_languages);
    $language_options = array(
      t('Already added languages') => $existing_languages,
      t('Languages not yet added') => language_admin_predefined_list()
    );
  }

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
    '#options' => $language_options,
    '#default_value' => $default,
    '#description' => t('Choose the language you want to add strings into. If you choose a language which is not yet set up, it will be added.'),
  );
  $form['import']['mode'] = array('#type' => 'radios',
    '#title' => t('Mode'),
    '#default_value' => LOCALE_IMPORT_KEEP,
    '#options' => array(
      LOCALE_IMPORT_OVERWRITE => t('Strings in the uploaded file replace existing ones, new ones are added. The plural format is updated.'),
      LOCALE_IMPORT_KEEP => t('Existing strings and the plural format are kept, only new strings are added.')
    ),
  );
  $form['import']['submit'] = array('#type' => 'submit', '#value' => t('Import'));

  return $form;
}

/**
 * Process the locale import form submission.
 */
function locale_translate_import_form_submit($form, &$form_state) {
  $validators = array('file_validate_extensions' => array('po'));
  // Ensure we have the file uploaded
  if ($file = file_save_upload('file', $validators)) {

    // Add language, if not yet supported
    drupal_static_reset('language_list');
    $languages = language_list();
    $langcode = $form_state['values']['langcode'];
    if (!isset($languages[$langcode])) {
      include_once DRUPAL_ROOT . '/core/includes/standard.inc';
      $predefined = standard_language_list();
      $language = (object) array(
        'langcode' => $langcode,
      );
      language_save($language);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($predefined[$langcode][0]))));
    }

    // Now import strings into the language
    if ($return = _locale_import_po($file, $langcode, $form_state['values']['mode']) == FALSE) {
      $variables = array('%filename' => $file->filename);
      drupal_set_message(t('The translation import of %filename failed.', $variables), 'error');
      watchdog('locale', 'The translation import of %filename failed.', $variables, WATCHDOG_ERROR);
    }
  }
  else {
    drupal_set_message(t('File to import not found.'), 'error');
    $form_state['redirect'] = 'admin/config/regional/translate/import';
    return;
  }

  $form_state['redirect'] = 'admin/config/regional/translate';
  return;
}

/**
 * User interface for the translation export screen.
 */
function locale_translate_export_screen() {
  // Get all enabled languages, except English, if we should not translate that.
  drupal_static_reset('language_list');
  $languages = language_list(TRUE);
  $language_options = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $language_options[$langcode] = $language->name;
    }
  }

  $output = '';
  // Offer translation export if any language is set up.
  if (!empty($language_options)) {
    $elements = drupal_get_form('locale_translate_export_po_form', $language_options);
    $output = drupal_render($elements);
  }
  $elements = drupal_get_form('locale_translate_export_pot_form');
  $output .= drupal_render($elements);
  return $output;
}

/**
 * Form to export PO files for the languages provided.
 *
 * @param $names
 *   An associate array with localized language names
 */
function locale_translate_export_po_form($form, &$form_state, $names) {
  $form['export_title'] = array('#type' => 'item',
    '#title' => t('Export translation'),
  );
  $form['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $names,
    '#description' => t('Select the language to export in Gettext Portable Object (<em>.po</em>) format.'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  return $form;
}

/**
 * Translation template export form.
 */
function locale_translate_export_pot_form() {
  // Complete template export of the strings
  $form['export_title'] = array('#type' => 'item',
    '#title' => t('Export template'),
    '#description' => t('Generate a Gettext Portable Object Template (<em>.pot</em>) file with all strings from the Drupal locale database.'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  // Reuse PO export submission callback.
  $form['#submit'][] = 'locale_translate_export_po_form_submit';
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
  _locale_export_po($language, _locale_export_po_generate($language, _locale_export_get_strings($language)));
}

/**
 * Set a batch for newly added language.
 */
function locale_translate_add_language_set_batch($langcode) {
  // See if we have language files to import for the newly added language,
  // collect and import them.
  if ($batch = locale_translate_batch_import_files($langcode, TRUE)) {
    batch_set($batch);
  }
}

/**
 * Prepare a batch to import all translations.
 *
 * @param $langcode
 *   (optional) Language code to limit files being imported.
 * @param $finish_feedback
 *   (optional) Whether to give feedback to the user when finished.
 *
 * @todo
 *   Integrate with update status to identify projects needed and integrate
 *   l10n_update functionality to feed in translation files alike.
 *   See http://drupal.org/node/1191488.
 */
function locale_translate_batch_import_files($langcode = NULL, $finish_feedback = FALSE) {
  $directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations');
  $files = array();
  if (!empty($langcode)) {
    $langcodes = array($langcode);
  }
  else {
    // If langcode was not provided, make sure to only import files for the
    // languages we have enabled.
    $langcodes = array_keys(language_list());
  }
  foreach ($langcodes as $langcode) {
    $files = array_merge($files, file_scan_directory($directory, '!' . (!empty($langcode) ? '\.' . preg_quote($langcode, '!') : '') . '\.po$!', array('recurse' => FALSE)));
  }
  return locale_translate_batch_build($files, $finish_feedback);
}

/**
 * Build a locale batch from an array of files.
 *
 * @param $files
 *   Array of file objects to import.
 * @param $finish_feedback
 *   (optional) Whether to give feedback to the user when finished.
 *
 * @return
 *   A batch structure or FALSE if $files was empty.
 */
function locale_translate_batch_build($files, $finish_feedback = FALSE) {
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call locale_translate_batch_import for every batch operation.
      $operations[] = array('locale_translate_batch_import', array($file->uri));
    }
    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => drupal_get_path('module', 'locale') . '/locale.bulk.inc',
    );
    if ($finish_feedback) {
      $batch['finished'] = 'locale_translate_batch_finished';
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
function locale_translate_batch_import($filepath, &$context) {
  // The filename is either {langcode}.po or {prefix}.{langcode}.po, so
  // we can extract the language code to use for the import from the end.
  if (preg_match('!(/|\.)([^\./]+)\.po$!', $filepath, $langcode)) {
    $file = (object) array('filename' => drupal_basename($filepath), 'uri' => $filepath);
    _locale_import_read_po('db-store', $file, LOCALE_IMPORT_KEEP, $langcode[2]);
    $context['results'][] = $filepath;
  }
}

/**
 * Finished callback of system page locale import batch.
 */
function locale_translate_batch_finished($success, $results) {
  if ($success) {
    drupal_set_message(format_plural(count($results), 'One translation file imported.', '@count translation files imported.'));
  }
}
