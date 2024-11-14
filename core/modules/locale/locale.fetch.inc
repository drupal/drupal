<?php

/**
 * @file
 */

use Drupal\Core\Batch\BatchBuilder;

/**
 * Load the common translation API.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue: https://www.drupal.org/node/1834298.
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
function locale_translation_batch_update_build($projects = [], $langcodes = [], $options = []) {
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());
  $status_options = $options;
  $status_options['finish_feedback'] = FALSE;

  $batch_builder = (new BatchBuilder())
    ->setFile(\Drupal::service('extension.list.module')->getPath('locale') . '/locale.batch.inc')
    ->setTitle(t('Updating translations'))
    ->setErrorMessage(t('Error importing translation files'))
    ->setFinishCallback('locale_translation_batch_fetch_finished');
  // Check status of local and remote translation files.
  $operations = _locale_translation_batch_status_operations($projects, $langcodes, $status_options);
  // Download and import translations.
  $operations = array_merge($operations, _locale_translation_fetch_operations($projects, $langcodes, $options));
  array_walk($operations, function ($operation) use ($batch_builder) {
    call_user_func_array([$batch_builder, 'addOperation'], $operation);
  });

  return $batch_builder->toArray();
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
function locale_translation_batch_fetch_build($projects = [], $langcodes = [], $options = []) {
  $projects = $projects ? $projects : array_keys(locale_translation_get_projects());
  $langcodes = $langcodes ? $langcodes : array_keys(locale_translatable_language_list());

  $batch_builder = (new BatchBuilder())
    ->setTitle(t('Updating translations.'))
    ->setErrorMessage(t('Error importing translation files'))
    ->setFile(\Drupal::service('extension.list.module')->getPath('locale') . '/locale.batch.inc')
    ->setFinishCallback('locale_translation_batch_fetch_finished');
  $operations = _locale_translation_fetch_operations($projects, $langcodes, $options);
  array_walk($operations, function ($operation) use ($batch_builder) {
    call_user_func_array([$batch_builder, 'addOperation'], $operation);
  });
  return $batch_builder->toArray();
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
  $operations = [];

  foreach ($projects as $project) {
    foreach ($langcodes as $langcode) {
      if (locale_translation_use_remote_source()) {
        $operations[] = ['locale_translation_batch_fetch_download', [$project, $langcode]];
      }
      $operations[] = ['locale_translation_batch_fetch_import', [$project, $langcode, $options]];
    }
  }

  return $operations;
}
