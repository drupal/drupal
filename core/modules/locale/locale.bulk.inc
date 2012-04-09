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
      t('Existing languages') => $existing_languages,
      t('Languages not yet added') => language_admin_predefined_list()
    );
  }

  $form['file'] = array(
    '#type' => 'file',
    '#title' => t('Translation file'),
    '#size' => 50,
    '#description' => t('A Gettext Portable Object (<em>.po</em>) file.'),
  );
  $form['langcode'] = array(
    '#type' => 'select',
    '#title' => t('Language'),
    '#options' => $language_options,
    '#default_value' => $default,
  );

  $form['customized'] = array(
    '#title' => t('Treat imported strings as custom translations'),
    '#type' => 'checkbox',
  );
  $form['overwrite_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );
  $form['overwrite_options']['not_customized'] = array(
    '#title' => t('Overwrite non-customized translations'),
    '#type' => 'checkbox',
    '#states' => array(
      'checked' => array(
         ':input[name="customized"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['overwrite_options']['customized'] = array(
    '#title' => t('Overwrite existing customized translations'),
    '#type' => 'checkbox',
  );

  $form['actions'] = array(
    '#type' => 'actions'
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import')
  );
  return $form;
}

/**
 * Processes the locale import form submission.
 */
function locale_translate_import_form_submit($form, &$form_state) {
  $validators = array('file_validate_extensions' => array('po'));

  // Ensure we have the file uploaded.
  if ($file = file_save_upload('file', $validators)) {

    // Add language, if not yet supported.
    $language = language_load($form_state['values']['langcode']);
    if (empty($language)) {
      include_once DRUPAL_ROOT . '/core/includes/standard.inc';
      $predefined = standard_language_list();
      $language = (object) array(
        'langcode' => $form_state['values']['langcode'],
      );
      $language = language_save($language);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($language->name))));
    }
    $customized = $form_state['values']['customized'] ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED;

    // Now import strings into the language
    if ($return = _locale_import_po($file, $language->langcode, $form_state['values']['overwrite_options'], $customized) == FALSE) {
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
 * Builds form to export Gettext translation files.
 */
function locale_translate_export_form($form, &$form_state) {
  $languages = language_list(TRUE);
  $language_options = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $language_options[$langcode] = $language->name;
    }
  }
  $language_default = language_default();

  if (empty($language_options)) {
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => LANGUAGE_SYSTEM,
    );
    $form['langcode_text'] = array(
      '#type' => 'item',
      '#title' => t('Language'),
      '#markup' => t('No language available. The export will only contain source strings.'),
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => $language_options,
      '#default_value' => $language_default->langcode,
      '#empty_option' => t('Source text only, no translations'),
      '#empty_value' => LANGUAGE_SYSTEM,
    );
    $form['content_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Export options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
      '#states' => array(
        'invisible' => array(
           ':input[name="langcode"]' => array('value' => LANGUAGE_SYSTEM),
        ),
      ),
    );
    $form['content_options']['not_customized'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include non-customized translations'),
      '#default_value' => TRUE,
    );
    $form['content_options']['customized'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include customized translations'),
      '#default_value' => TRUE,
    );
    $form['content_options']['not_translated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include untranslated text'),
      '#default_value' => TRUE,
    );
  }

  $form['actions'] = array(
    '#type' => 'actions'
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export')
  );
  return $form;
}

/**
 * Processes a translation (or template) export form submission.
 */
function locale_translate_export_form_submit($form, &$form_state) {
  // If template is required, language code is not given.
  if ($form_state['values']['langcode'] != LANGUAGE_SYSTEM) {
    $language = language_load($form_state['values']['langcode']);
  }
  else {
    $language = NULL;
  }
  $content_options = isset($form_state['values']['content_options']) ? $form_state['values']['content_options'] : array();
  _locale_export_po($language, _locale_export_po_generate($language, _locale_export_get_strings($language, $content_options)));
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
    _locale_import_read_po('db-store', $file, array(), $langcode[2]);
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
