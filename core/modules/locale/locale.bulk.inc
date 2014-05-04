<?php

/**
 * @file
 * Mass import-export and batch import functionality for Gettext .po files.
 */

use Drupal\locale\Gettext;
use Drupal\Core\Language\Language;
use Drupal\file\FileInterface;

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

  if (!empty($options['langcode'])) {
    $langcodes = array($options['langcode']);
  }
  else {
    // If langcode was not provided, make sure to only import files for the
    // languages we have added.
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
  }

  $files = locale_translate_get_interface_translation_files(array(), $langcodes);

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
 * Get interface translation files present in the translations directory.
 *
 * @param array $projects
 *   Project names from which to get the translation files and history.
 *   Defaults to all projects.
 * @param array $langcodes
 *   Language codes from which to  get the translation files and history.
 *   Defaults to all languagues
 *
 * @return array
 *   An array of interface translation files keyed by their URI.
 */
function locale_translate_get_interface_translation_files($projects = array(), $langcodes = array()) {
  module_load_include('compare.inc', 'locale');
  $files = array();
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  // Scan the translations directory for files matching a name pattern
  // containing a project name and language code: {project}.{langcode}.po or
  // {project}-{version}.{langcode}.po.
  // Only files of known projects and languages will be returned.
  $directory = \Drupal::config('locale.settings')->get('translation.path');
  $result = file_scan_directory($directory, '![a-z_]+(\-[0-9a-z\.\-\+]+|)\.[^\./]+\.po$!', array('recurse' => FALSE));

  foreach ($result as $file) {
    // Update the file object with project name and version from the file name.
    $file = locale_translate_file_attach_properties($file);
    if (in_array($file->project, $projects)) {
      if (in_array($file->langcode, $langcodes)) {
        $files[$file->uri] = $file;
      }
    }
  }

  return $files;
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
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call locale_translate_batch_import for every batch operation.
      $operations[] = array('locale_translate_batch_import', array($file, $options));
    }
    // Save the translation status of all files.
    $operations[] =  array('locale_translate_batch_import_save', array());

    // Add a final step to refresh JavaScript and configuration strings.
    $operations[] = array('locale_translate_batch_refresh', array());

    $batch = array(
      'operations'    => $operations,
      'title'         => t('Importing interface translations'),
      'progress_message' => '',
      'error_message' => t('Error importing interface translations'),
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
 * @param object $file
 *   A file object of the gettext file to be imported. The file object must
 *   contain a language parameter (other than Language::LANGCODE_NOT_SPECIFIED). This
 *   is used as the language of the import.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'langcode': The language code.
 *   - 'overwrite_options': Overwrite options array as defined in
 *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
 *   - 'customized': Flag indicating whether the strings imported from $file
 *     are customized translations or come from a community source. Use
 *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
 *     LOCALE_NOT_CUSTOMIZED.
 *   - 'message': Alternative message to display during import. Note, this must
 *     be sanitized text.
 *
 * @param $context
 *   Contains a list of files imported.
 */
function locale_translate_batch_import($file, $options, &$context) {
  // Merge the default values in the $options array.
  $options += array(
    'overwrite_options' => array(),
    'customized' => LOCALE_NOT_CUSTOMIZED,
  );

  if (isset($file->langcode) && $file->langcode != Language::LANGCODE_NOT_SPECIFIED) {

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
        if (isset($options['message'])) {
          $context['message'] = t('!message (@percent%).', array('!message' => $options['message'], '@percent' => (int) ($context['finished'] * 100)));
        }
        else {
          $context['message'] = t('Importing translation file: %filename (@percent%).', array('%filename' => $file->filename, '@percent' => (int) ($context['finished'] * 100)));
        }
      }
      else {
        // We are finished here.
        $context['finished'] = 1;

        // Store the file data for processing by the next batch operation.
        $file->timestamp = filemtime($file->uri);
        $context['results']['files'][$file->uri] = $file;
        $context['results']['languages'][$file->uri] = $file->langcode;
      }

      // Add the reported values to the statistics for this file.
      // Each import iteration reports statistics in an array. The results of
      // each iteration are added and merged here and stored per file.
      if (!isset($context['results']['stats']) || !isset($context['results']['stats'][$file->uri])) {
        $context['results']['stats'][$file->uri] = array();
      }
      foreach ($report as $key => $value) {
        if (is_numeric($report[$key])) {
          if (!isset($context['results']['stats'][$file->uri][$key])) {
            $context['results']['stats'][$file->uri][$key] = 0;
          }
          $context['results']['stats'][$file->uri][$key] += $report[$key];
        }
        elseif (is_array($value)) {
          $context['results']['stats'][$file->uri] += array($key => array());
          $context['results']['stats'][$file->uri][$key] = array_merge($context['results']['stats'][$file->uri][$key], $value);
        }
      }
    }
    catch (Exception $exception) {
      // Import failed. Store the data of the failing file.
      $context['results']['failed_files'][] = $file;
      watchdog('locale', 'Unable to import translations file: @file', array('@file' => $file->uri));
    }
  }
}

/**
 * Batch callback: Save data of imported files.
 *
 * @param $context
 *   Contains a list of imported files.
 */
function locale_translate_batch_import_save($context) {
  if (isset($context['results']['files'])) {
    foreach ($context['results']['files'] as $file) {
      // Update the file history if both project and version are known. This
      // table is used by the automated translation update function which tracks
      // translation status of module and themes in the system. Other
      // translation files are not tracked and are therefore not stored in this
      // table.
      if ($file->project && $file->version) {
        $file->last_checked = REQUEST_TIME;
        locale_translation_update_file_history($file);
      }
    }
    $context['message'] = t('Translations imported.');
  }
}

/**
 * Refreshs translations after importing strings.
 *
 * @param array $context
 *   Contains a list of strings updated and information about the progress.
 */
function locale_translate_batch_refresh(array &$context) {
  if (!isset($context['sandbox']['refresh'])) {
    $strings = $langcodes = array();
    if (isset($context['results']['stats'])) {
      // Get list of unique string identifiers and language codes updated.
      $langcodes = array_unique(array_values($context['results']['languages']));
      foreach ($context['results']['stats'] as $report) {
        $strings = array_merge($strings, $report['strings']);
      }
    }
    if ($strings) {
      // Initialize multi-step string refresh.
      $context['message'] = t('Updating translations for JavaScript and configuration strings.');
      $context['sandbox']['refresh']['strings'] = array_unique($strings);
      $context['sandbox']['refresh']['languages'] = $langcodes;
      $context['sandbox']['refresh']['names'] = array();
      $context['results']['stats']['config'] = 0;
      $context['sandbox']['refresh']['count'] = count($strings);

      // We will update strings on later steps.
      $context['finished'] = 1 - 1 / $context['sandbox']['refresh']['count'];
    }
    else {
      $context['finished'] = 1;
    }
  }
  elseif ($name = array_shift($context['sandbox']['refresh']['names'])) {
    // Refresh all languages for one object at a time.
    $count = locale_config_update_multiple(array($name), $context['sandbox']['refresh']['languages']);
    $context['results']['stats']['config'] += $count;
  }
  elseif (!empty($context['sandbox']['refresh']['strings'])) {
    // Not perfect but will give some indication of progress.
    $context['finished'] = 1 - count($context['sandbox']['refresh']['strings']) / $context['sandbox']['refresh']['count'];
    // Pending strings, refresh 100 at a time, get next pack.
    $next = array_slice($context['sandbox']['refresh']['strings'], 0, 100);
    array_splice($context['sandbox']['refresh']['strings'], 0, count($next));
    // Clear cache and force refresh of JavaScript translations.
    _locale_refresh_translations($context['sandbox']['refresh']['languages'], $next);
    // Check whether we need to refresh configuration objects.
    if ($names = \Drupal\locale\Locale::config()->getStringNames($next)) {
      $context['sandbox']['refresh']['names'] = $names;
    }
  }
  else {
    $context['finished'] = 1;
  }
}

/**
 * Finished callback of system page locale import batch.
 */
function locale_translate_batch_finished($success, $results) {
  if ($success) {
    $additions = $updates = $deletes = $skips = $config = 0;
    if (isset($results['failed_files'])) {
      if (\Drupal::moduleHandler()->moduleExists('dblog')) {
        $message = format_plural(count($results['failed_files']), 'One translation file could not be imported. <a href="@url">See the log</a> for details.', '@count translation files could not be imported. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
      }
      else {
        $message = format_plural(count($results['failed_files']), 'One translation files could not be imported. See the log for details.', '@count translation files could not be imported. See the log for details.');
      }
      drupal_set_message($message, 'error');
    }
    if (isset($results['files'])) {
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
      drupal_set_message(format_plural(count($results['files']),
        'One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.',
        '@count translation files imported. %number translations were added, %update translations were updated and %delete translations were removed.',
        array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)
      ));
      watchdog('locale', 'Translations imported: %number added, %update updated, %delete removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes));

      if ($skips) {
        if (\Drupal::moduleHandler()->moduleExists('dblog')) {
          $message = format_plural($skips, 'One translation string was skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
        }
        else {
          $message = format_plural($skips, 'One translation string was skipped because of disallowed or malformed HTML. See the log for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.');
        }
        drupal_set_message($message, 'warning');
        watchdog('locale', '@count disallowed HTML string(s) in files: @files.', array('@count' => $skips, '@files' => implode(',', $skipped_files)), WATCHDOG_WARNING);
      }
    }
  }
  // Add messages for configuration too.
  if (isset($results['stats']['config'])) {
    locale_config_batch_finished($success, $results);
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
 * Generates file properties from filename and options.
 *
 * An attempt is made to determine the translation language, project name and
 * project version from the file name. Supported file name patterns are:
 * {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or {langcode}.po.
 * Alternatively the translation language can be set using the $options.
 *
 * @param object $file
 *   A file object of the gettext file to be imported.
 * @param array $options
 *   An array with options:
 *   - 'langcode': The language code. Overrides the file language.
 *
 * @return object
 *   Modified file object.
 */
function locale_translate_file_attach_properties($file, $options = array()) {
  // If $file is a file entity, convert it to a stdClass.
  if ($file instanceof FileInterface) {
    $file = (object) array(
      'filename' => $file->getFilename(),
      'uri' => $file->getFileUri(),
    );
  }

  // Extract project, version and language code from the file name. Supported:
  // {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or {langcode}.po
  preg_match('!
    (                       # project OR project and version OR emtpy (group 1)
      ([a-z_]+)             # project name (group 2)
      \.                    # .
      |                     # OR
      ([a-z_]+)             # project name (group 3)
      \-                    # -
      ([0-9a-z\.\-\+]+)     # version (group 4)
      \.                    # .
      |                     # OR
    )                       # (empty)
    ([^\./]+)               # language code (group 5)
    \.                      # .
    po                      # po extension
    $!x', $file->filename, $matches);
  if (isset($matches[5])) {
    $file->project = $matches[2] . $matches[3];
    $file->version = $matches[4];
    $file->langcode = isset($options['langcode']) ? $options['langcode'] : $matches[5];
  }
  else {
    $file->langcode = Language::LANGCODE_NOT_SPECIFIED;
  }
  return $file;
}

/**
 * Deletes interface translation files and translation history records.
 *
 * @param array $projects
 *   Project names from which to delete the translation files and history.
 *   Defaults to all projects.
 * @param array $langcodes
 *   Language codes from which to delete the translation files and history.
 *   Defaults to all languagues
 *
 * @return boolean
 *   TRUE if files are removed successfully. FALSE if one or more files could
 *   not be deleted.
 */
function locale_translate_delete_translation_files($projects = array(), $langcodes = array()) {
  $fail = FALSE;
  locale_translation_file_history_delete($projects, $langcodes);

  // Delete all translation files from the translations directory.
  if ($files = locale_translate_get_interface_translation_files($projects, $langcodes)) {
    foreach ($files as $file) {
      $success = file_unmanaged_delete($file->uri);
      if (!$success) {
        $fail = TRUE;
      }
    }
  }
  return !$fail;
}

/**
 * Builds a locale batch to refresh configuration.
 *
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'finish_feedback': (optional) Whether or not to give feedback to the user
 *     when the batch is finished. Defaults to TRUE.
 * @param array $langcodes
 *   (optional) Array of language codes. Defaults to all translatable languages.
 * @param array $components
 *   (optional) Array of component lists indexed by type. If not present or it
 *   is an empty array, it will update all components.
 *
 * @return array
 *   The batch definition.
 */
function locale_config_batch_update_components(array $options, $langcodes = array(), $components = array()) {
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  if ($langcodes && $names = \Drupal\locale\Locale::config()->getComponentNames($components)) {
    return locale_config_batch_build($names, $langcodes, $options);
  }
}

/**
 * Creates a locale batch to refresh specific configuration.
 *
 * @param array $names
 *   List of configuration object names (which are strings) to update.
 * @param array $langcodes
 *   List of language codes to refresh.
 * @param array $options
 *   (optional) An array with options that can have the following elements:
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Defaults to TRUE.
 *
 * @return array
 *   The batch definition.
 *
 * @see locale_config_batch_refresh_name()
 */
function locale_config_batch_build(array $names, array $langcodes, $options = array()) {
  $options += array('finish_feedback' => TRUE);
  $i = 0;
  $batch_names = array();
  foreach ($names as $name) {
    $batch_names[] = $name;
    $i++;
    // During installation the caching of configuration objects is disabled
    // so it is very expensive to initialize the \Drupal::config() object on each request.
    // We batch a small number of configuration object upgrades together to
    // improve the overall performance of the process.
    if ($i % 20 == 0) {
      $operations[] = array('locale_config_batch_refresh_name', array($batch_names, $langcodes));
      $batch_names = array();
    }
  }
  if (!empty($batch_names)) {
    $operations[] = array('locale_config_batch_refresh_name', array($batch_names, $langcodes));
  }
  $batch = array(
    'operations'    => $operations,
    'title'         => t('Updating configuration translations'),
    'init_message'  => t('Starting configuration update'),
    'error_message' => t('Error updating configuration translations'),
    'file'          => drupal_get_path('module', 'locale') . '/locale.bulk.inc',
  );
  if (!empty($options['finish_feedback'])) {
    $batch['completed'] = 'locale_config_batch_finished';
  }
  return $batch;
}

/**
 * Performs configuration translation refresh as a batch step.
 *
 * @param string $name
 *   Name of configuration object to update.
 * @param array $langcodes
 *   (optional) Array of language codes to update. Defaults to all languages.
 * @param array $context
 *   Contains a list of files imported.
 *
 * @see locale_config_batch_build()
 */
function locale_config_batch_refresh_name(array $names, array $langcodes, array &$context) {
  if (!isset($context['result']['stats']['config'])) {
    $context['result']['stats']['config'] = 0;
  }
  $context['result']['stats']['config'] += locale_config_update_multiple($names, $langcodes);
  foreach ($names as $name) {
    $context['result']['names'][] = $name;
  }
  $context['result']['langcodes'] = $langcodes;
  $context['finished'] = 1;
}

/**
 * Finishes callback of system page locale import batch.
 *
 * @see locale_config_batch_build()
 *
 * @param bool $success
 *   Information about the success of the batch import.
 * @param array $results
 *   Information about the results of the batch import.
 */
function locale_config_batch_finished($success, array $results) {
  if ($success) {
    $configuration = isset($results['stats']['config']) ? $results['stats']['config'] : 0;
    if ($configuration) {
      drupal_set_message(t('The configuration was successfully updated. There are %number configuration objects updated.', array('%number' => $configuration)));
      watchdog('locale', 'The configuration was successfully updated. %number configuration objects updated.', array('%number' => $configuration));
    }
    else {
      drupal_set_message(t('No configuration objects have been updated.'));
      watchdog('locale', 'No configuration objects have been updated.', array(), WATCHDOG_WARNING);
    }
  }
}

/**
 * Updates all configuration for names / languages.
 *
 * @param array $names
 *   Array of names of configuration objects to update.
 * @param array $langcodes
 *   (optional) Array of language codes to update. Defaults to all languages.
 *
 * @return int
 *   Number of configuration objects retranslated.
 */
function locale_config_update_multiple(array $names, $langcodes = array()) {
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $count = 0;
  foreach ($names as $name) {
    $wrapper = \Drupal\locale\Locale::config()->get($name);
    foreach ($langcodes as $langcode) {
      $translation = $wrapper->getValue() ? $wrapper->getTranslation($langcode)->getValue() : NULL;
      if ($translation) {
        \Drupal\locale\Locale::config()->saveTranslationData($name, $langcode, $translation);
        $count++;
      }
      else {
        \Drupal\locale\Locale::config()->deleteTranslationData($name, $langcode);
      }
    }
  }
  return $count;
}
