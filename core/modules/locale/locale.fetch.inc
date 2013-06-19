<?php

/**
 * @file
 * The API for download and import of translations from remote and local sources.
 */

/**
 * Load the common translation API.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue http://drupal.org/node/1834298
require_once __DIR__ . '/locale.translation.inc';

/**
 * Builds a batch to check, download and import project translations.
 *
 * @param array $projects
 *   Array of project names for which to update the translations. Defaults to
 *   all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 * @param array $options
 *   Array of import options. See locale_translate_batch_import_files().
 *
 * @return array
 *   Batch definition array.
 */
function locale_translation_batch_update_build($projects = array(), $langcodes = array(), $options = array()) {
  module_load_include('compare.inc', 'locale');
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  $operations = _locale_translation_batch_status_operations($projects, $langcodes);
  $operations = array_merge($operations, _locale_translation_fetch_operations($projects, $langcodes, $options));

  $batch = array(
    'operations' => $operations,
    'title' => t('Updating translations'),
    'progress_message' => '',
    'error_message' => t('Error importing translation files'),
    'finished' => 'locale_translation_batch_fetch_finished',
    'file' => drupal_get_path('module', 'locale') . '/locale.batch.inc',
  );
  return $batch;
}

/**
 * Builds a batch to download and import project translations.
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 * @param array $options
 *   Array of import options. See locale_translate_batch_import_files().
 *
 * @return array
 *   Batch definition array.
 */
function locale_translation_batch_fetch_build($projects = array(), $langcodes = array(), $options = array()) {
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  $batch = array(
    'operations' => _locale_translation_fetch_operations($projects, $langcodes, $options),
    'title' => t('Updating translations.'),
    'progress_message' => '',
    'error_message' => t('Error importing translation files'),
    'finished' => 'locale_translation_batch_fetch_finished',
    'file' => drupal_get_path('module', 'locale') . '/locale.batch.inc',
  );
  return $batch;
}

/**
 * Helper function to construct the batch operations to fetch translations.
 *
 * @param array $projects
 *   Array of project names for which to check the state of translation files.
 *   Defaults to all translatable projects.
 * @param array $langcodes
 *   Array of language codes. Defaults to all translatable languages.
 * @param array $options
 *   Array of import options.
 *
 * @return array
 *   Array of batch operations.
 */
function _locale_translation_fetch_operations($projects, $langcodes, $options) {
  $operations = array();
  $config = config('locale.settings');

  $operations[] = array('locale_translation_batch_fetch_sources', array($projects, $langcodes));
  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      if (locale_translation_use_remote_source()) {
        $operations[] = array('locale_translation_batch_fetch_download', array($project, $langcode));
      }
      $operations[] = array('locale_translation_batch_fetch_import', array($project, $langcode, $options));
    }
  }

  // Update and save the translation status.
  $operations[] = array('locale_translation_batch_fetch_update_status', array());

  // Update and save the source status. New translation files have been
  // downloaded, so other sources will be newer. We update the status now.
  $operations[] = array('locale_translation_batch_status_compare', array());

  return $operations;
}
