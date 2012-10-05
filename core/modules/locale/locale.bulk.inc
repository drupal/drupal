<?php

/**
 * @file
 * Mass import-export and batch import functionality for Gettext .po files.
 */

use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\locale\Gettext;
use Drupal\locale\PoDatabaseReader;
use Drupal\Core\Language\Language;


/**
 * Form constructor for the translation import screen.
 *
 * @see locale_translate_import_form_submit()
 * @ingroup forms
 */
function locale_translate_import_form($form, &$form_state) {
  drupal_static_reset('language_list');
  $languages = language_list();

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

  $validators = array(
    'file_validate_extensions' => array('po'),
    'file_validate_size' => array(file_upload_max_size()),
  );
  $form['file'] = array(
    '#type' => 'file',
    '#title' => t('Translation file'),
    '#description' => theme('file_upload_help', array('description' => t('A Gettext Portable Object file.'), 'upload_validators' => $validators)),
    '#size' => 50,
    '#upload_validators' => $validators,
    '#attributes' => array('class' => array('file-import-input')),
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'locale') . '/locale.bulk.js' => array(),
      ),
    ),
  );
  $form['langcode'] = array(
    '#type' => 'select',
    '#title' => t('Language'),
    '#options' => $language_options,
    '#default_value' => $default,
    '#attributes' => array('class' => array('langcode-input')),
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
 * Form submission handler for locale_translate_import_form().
 */
function locale_translate_import_form_submit($form, &$form_state) {
  // Ensure we have the file uploaded.
  if ($file = file_save_upload('file', $form['file']['#upload_validators'], 'translations://')) {

    // Add language, if not yet supported.
    $language = language_load($form_state['values']['langcode']);
    if (empty($language)) {
      $language = new Language(array(
        'langcode' => $form_state['values']['langcode']
      ));
      $language = language_save($language);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($language->name))));
    }
    $options = array(
      'langcode' => $form_state['values']['langcode'],
      'overwrite_options' => $form_state['values']['overwrite_options'],
      'customized' => $form_state['values']['customized'] ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED,
    );
    $batch = locale_translate_batch_build(array($file->uri => $file), $options);
    batch_set($batch);
  }
  else {
    form_set_error('file', t('File to import not found.'));
    $form_state['rebuild'] = TRUE;
    return;
  }

  $form_state['redirect'] = 'admin/config/regional/translate';
  return;
}

/**
 * Form constructor for the Gettext translation files export form.
 *
 * @see locale_translate_export_form_submit()
 * @ingroup forms
 */
function locale_translate_export_form($form, &$form_state) {
  $languages = language_list();
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
 * Form submission handler for locale_translate_export_form().
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
  $reader = new PoDatabaseReader();
  $languageName = '';
  if ($language != NULL) {
    $reader->setLangcode($language->langcode);
    $reader->setOptions($content_options);
    $languages = language_list();
    $languageName = isset($languages[$language->langcode]) ? $languages[$language->langcode]->name : '';
    $filename = $language->langcode .'.po';
  }
  else {
    // Template required.
    $filename = 'drupal.pot';
  }

  $item = $reader->readItem();
  if (!empty($item)) {
    $uri = tempnam('temporary://', 'po_');
    $header = $reader->getHeader();
    $header->setProjectName(config('system.site')->get('name'));
    $header->setLanguageName($languageName);

    $writer = new PoStreamWriter;
    $writer->setUri($uri);
    $writer->setHeader($header);

    $writer->open();
    $writer->writeItem($item);
    $writer->writeItems($reader);
    $writer->close();

    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: text/plain; charset=utf-8");
    print file_get_contents($uri);
    drupal_exit();
  }
  else {
    drupal_set_message('Nothing to export.');
  }
}

/**
 * Sets a batch for a newly-added language.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code, required.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 */
function locale_translate_add_language_set_batch($options) {
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'finish_feedback' => TRUE,
  );
  // See if we have language files to import for the newly added language,
  // collect and import them.
  if ($batch = locale_translate_batch_import_files($options)) {
    batch_set($batch);
  }
}

/**
 * Prepare a batch to import all translations.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code. Optional, defaults to NULL, which means
 *     that the language will be detected from the name of the files.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 *
 * @param $force
 *   (optional) Import all available files, even if they were imported before.
 *
 * @todo
 *   Integrate with update status to identify projects needed and integrate
 *   l10n_update functionality to feed in translation files alike.
 *   See http://drupal.org/node/1191488.
 */
function locale_translate_batch_import_files($options, $force = FALSE) {
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'finish_feedback' => TRUE,
  );
  $files = array();
  if (!empty($options['langcode'])) {
    $langcodes = array($options['langcode']);
  }
  else {
    // If langcode was not provided, make sure to only import files for the
    // languages we have enabled.
    $langcodes = array_keys(language_list());
  }
  foreach ($langcodes as $langcode) {
    $files = array_merge($files, locale_translate_get_interface_translation_files($langcode));
  }
  if (!$force) {
    $result = db_select('locale_file', 'lf')
      ->fields('lf', array('langcode', 'uri', 'timestamp'))
      ->condition('langcode', $langcodes)
      ->execute()
      ->fetchAllAssoc('uri');
    foreach ($result as $uri => $info) {
      if (isset($files[$uri]) && filemtime($uri) <= $info->timestamp) {
        // The file is already imported and not changed since the last import.
        // Remove it from file list and don't import it again.
        unset($files[$uri]);
      }
    }
  }
  return locale_translate_batch_build($files, $options);
}

/**
 * Get an array of available interface translation file.
 *
 * @param $langcode
 *   The langcode for the interface translation files. Pass NULL to get all
 *   available interface translation files.
 *
 * @return array
 *   An array of interface translation files.
 */
function locale_translate_get_interface_translation_files($langcode = NULL) {
  $directory = variable_get('locale_translate_file_directory', conf_path() . '/files/translations');
  $return = file_scan_directory($directory, '!' . (!empty($langcode) ? '\.' . preg_quote($langcode, '!') : '') . '\.po$!', array('recurse' => FALSE));

  foreach ($return as $filepath => $file) {
    $file->uri = 'translations://' . $file->filename;
    $return[$file->uri] = $file;
    unset($return[$filepath]);
  }
  return $return;
}

/**
 * Build a locale batch from an array of files.
 *
 * @param $files
 *   Array of file objects to import.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code. Optional, defaults to NULL, which means
 *     that the language will be detected from the name of the files.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 *
 * @return
 *   A batch structure or FALSE if $files was empty.
 */
function locale_translate_batch_build($files, $options) {
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'finish_feedback' => TRUE,
  );
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call locale_translate_batch_import for every batch operation.
      $operations[] = array('locale_translate_batch_import', array($file->uri, $options));
    }
    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => drupal_get_path('module', 'locale') . '/locale.bulk.inc',
    );
    if ($options['finish_feedback']) {
      $batch['finished'] = 'locale_translate_batch_finished';
    }
    return $batch;
  }
  return FALSE;
}

/**
 * Perform interface translation import as a batch step.
 *
 * The given filepath is matched against ending with '{langcode}.po'. When
 * matched the filepath is added to batch context.
 *
 * @param $filepath
 *   Path to a file to import.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code, required.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *
 * @param $context
 *   Contains a list of files imported.
 */
function locale_translate_batch_import($filepath, $options, &$context) {
  // Merge the default values in the $options array.
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
  );
  // The filename is either {langcode}.po or {prefix}.{langcode}.po, so
  // we can extract the language code to use for the import from the end.
  if (isset($options['langcode']) && $options['langcode'] ||
      preg_match('!(/|\.)([^\./]+)\.po$!', $filepath, $matches)) {
    $basename = drupal_basename($filepath);
    $file = entity_create('file', array('filename' => $basename, 'uri' => 'translations://'. $basename));
    // We need only the last match, but only if the langcode is not explicitly
    // specified in the $options array.
    if (!$options['langcode'] && is_array($matches)) {
      $options['langcode'] = array_pop($matches);
    }
    try {
      if (empty($context['sandbox'])) {
        $context['sandbox']['parse_state'] = array(
          'filesize' => filesize(drupal_realpath($file->uri)),
          'chunk_size' => 200,
          'seek' => 0,
        );
      }
      // Update the seek and the number of items in the $options array().
      $options['seek'] = $context['sandbox']['parse_state']['seek'];
      $options['items'] = $context['sandbox']['parse_state']['chunk_size'];
      $report = GetText::fileToDatabase($file, $options);
      // If not yet finished with reading, mark progress based on size and
      // position.
      if ($report['seek'] < filesize($file->uri)) {
        $context['sandbox']['parse_state']['seek'] = $report['seek'];
        // Maximize the progress bar at 95% before completion, the batch API
        // could trigger the end of the operation before file reading is done,
        // because of floating point inaccuracies. See
        // http://drupal.org/node/1089472
        $context['finished'] = min(0.95, $report['seek'] / filesize($file->uri));
        $context['message'] = t('Importing file: %filename (@percent%)', array('%filename' => $file->filename, '@percent' => (int) ($context['finished'] * 100)));
      }
      else {
        // We are finished here.
        $context['finished'] = 1;
        $file->langcode = $options['langcode'];
        $file->timestamp = filemtime($file->uri);
        locale_translate_update_file_history($file);
        $context['results']['files'][$filepath] = $filepath;
      }
      // Add the values from the report to the stats for this file.
      if (!isset($context['results']['stats']) || !isset($context['results']['stats'][$filepath])) {
        $context['results']['stats'][$filepath] = array();
      }
      foreach ($report as $key => $value) {
        if (is_numeric($report[$key])) {
          if (!isset($context['results']['stats'][$filepath][$key])) {
            $context['results']['stats'][$filepath][$key] = 0;
          }
          $context['results']['stats'][$filepath][$key] += $report[$key];
        }
      }
    }
    catch (Exception $exception) {
      $context['results']['files'][$filepath] = $filepath;
      $context['results']['failed_files'][$filepath] = $filepath;
    }
  }
}

/**
 * Finished callback of system page locale import batch.
 */
function locale_translate_batch_finished($success, $results) {
  if ($success) {
    $additions = $updates = $deletes = $skips = 0;
    drupal_set_message(format_plural(count($results['files']), 'One translation file imported.', '@count translation files imported.'));
    $skipped_files = array();
    // If there are no results and/or no stats (eg. coping with an empty .po
    // file), simply do nothing.
    if ($results && isset($results['stats'])) {
      foreach ($results['stats'] as $filepath => $report) {
        $additions += $report['additions'];
        $updates += $report['updates'];
        $deletes += $report['deletes'];
        $skips += $report['skips'];
        if ($report['skips'] > 0) {
          $skipped_files[] = $filepath;
        }
      }
    }
    drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)));
    watchdog('locale', 'The translation was succesfully imported. %number new strings added, %update updated and %delete removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes));
    if ($skips) {
      if (module_exists('dblog')) {
        $skip_message = format_plural($skips, 'A translation string was skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
      }
      else {
        $skip_message = format_plural($skips, 'A translation string was skipped because of disallowed or malformed HTML. See the log for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.');
      }
      drupal_set_message($skip_message, 'error');
      watchdog('locale', '@count disallowed HTML string(s) in files: @files.', array('@count' => $skips, '@files' => implode(',', $skipped_files)), WATCHDOG_WARNING);
    }

    // Clear cache and force refresh of JavaScript translations.
    _locale_invalidate_js();
    cache()->invalidateTags(array('locale' => TRUE));
  }
}

/**
 * Creates a file object and populates the timestamp property.
 *
 * @param $filepath
 *   The filepath of a file to import.
 *
 * @return
 *   An object representing the file.
 */
function locale_translate_file_create($filepath) {
  $file = new stdClass();
  $file->filename = drupal_basename($filepath);
  $file->uri = $filepath;
  $file->timestamp = filemtime($file->uri);
  return $file;
}

/**
 * Update the {locale_file} table.
 *
 * @param $file
 *   Object representing the file just imported.
 *
 * @return integer
 *   FALSE on failure. Otherwise SAVED_NEW or SAVED_UPDATED.
 *
 * @see drupal_write_record()
 */
function locale_translate_update_file_history($file) {
  // Update or write new record.
  if (db_query("SELECT uri FROM {locale_file} WHERE uri = :uri AND langcode = :langcode", array(':uri' => $file->uri, ':langcode' => $file->langcode))->fetchField()) {
    $update = array('uri', 'langcode');
  }
  else {
    $update = array();
  }
  return drupal_write_record('locale_file', $file, $update);
}

/**
 * Deletes all interface translation files depending on the langcode.
 *
 * @param $langcode
 *   A langcode or NULL. Pass NULL to delete all interface translation files.
 */
function locale_translate_delete_translation_files($langcode) {
  $files = locale_translate_get_interface_translation_files($langcode);
  $return = TRUE;
  if (!empty($files)) {
    foreach ($files as $file) {
      $success = file_unmanaged_delete($file->uri);
      if (!$success) {
        $return = FALSE;
      }
      else {
        // Remove the registered translation file if any.
        db_delete('locale_file')
          ->condition('langcode', $langcode)
          ->condition('uri', $file->uri)
          ->execute();
      }
    }
  }
  return $return;
}
