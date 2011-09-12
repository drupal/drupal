<?php

/**
 * @file
 * Mass import-export and batch import functionality for Gettext .po files.
 */

include_once DRUPAL_ROOT . '/includes/gettext.inc';

/**
 * User interface for the translation import screen.
 */
function locale_translate_import_form($form) {
  // Get all languages, except English
  drupal_static_reset('language_list');
  $names = locale_language_list('name');
  unset($names['en']);

  if (!count($names)) {
    $languages = _locale_prepare_predefined_list();
    $default = key($languages);
  }
  else {
    $languages = array(
      t('Already added languages') => $names,
      t('Languages not yet added') => _locale_prepare_predefined_list()
    );
    $default = key($names);
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
    '#options' => $languages,
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
    $languages = language_list('language');
    $langcode = $form_state['values']['langcode'];
    if (!isset($languages[$langcode])) {
      include_once DRUPAL_ROOT . '/includes/standard.inc';
      $predefined = standard_language_list();
      locale_add_language($langcode);
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
  // Get all languages, except English
  drupal_static_reset('language_list');
  $names = locale_language_list('name');
  unset($names['en']);
  $output = '';
  // Offer translation export if any language is set up.
  if (count($names)) {
    $elements = drupal_get_form('locale_translate_export_po_form', $names);
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
 *
 * @return
 *   A batch structure or FALSE if no files found.
 */
function locale_batch_by_language($langcode, $finished = NULL, $skip = array()) {
  // Collect all files to import for all enabled modules and themes.
  $files = array();
  $components = array();
  $query = db_select('system', 's');
  $query->fields('s', array('name', 'filename'));
  $query->condition('s.status', 1);
  if (count($skip)) {
    $query->condition('name', $skip, 'NOT IN');
  }
  $result = $query->execute();
  foreach ($result as $component) {
    // Collect all files for all components, names as $langcode.po or
    // with names ending with $langcode.po. This allows for filenames
    // like node-module.de.po to let translators use small files and
    // be able to import in smaller chunks.
    $files = array_merge($files, file_scan_directory(dirname($component->filename) . '/translations', '/(^|\.)' . $langcode . '\.po$/', array('recurse' => FALSE)));
    $components[] = $component->name;
  }

  return _locale_batch_build($files, $finished, $components);
}

/**
 * Prepare a batch to run when installing modules or enabling themes.
 *
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
    foreach ($result as $component) {
      if (in_array($component->name, $components)) {
        // Collect all files for this component in all enabled languages, named
        // as $langcode.po or with names ending with $langcode.po. This allows
        // for filenames like node-module.de.po to let translators use small
        // files and be able to import in smaller chunks.
        $files = array_merge($files, file_scan_directory(dirname($component->filename) . '/translations', '/(^|\.)(' . $language_list . ')\.po$/', array('recurse' => FALSE)));
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
 *   Array of files to import.
 * @param $finished
 *   Optional finished callback for the batch.
 * @param $components
 *   Optional list of component names the batch covers. Used in the installer.
 *
 * @return
 *   A batch structure.
 */
function _locale_batch_build($files, $finished = NULL, $components = array()) {
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call _locale_batch_import for every batch operation.
      $operations[] = array('_locale_batch_import', array($file->uri));
    }
    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => drupal_get_path('module', 'locale') . '/locale.bulk.inc',
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
    $file = (object) array('filename' => basename($filepath), 'uri' => $filepath);
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
