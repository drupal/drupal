<?php

/**
 * @file
 */

use Drupal\locale\LocaleFetch;

/**
 * Load the common translation API.
 */

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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)
 *   ->batchUpdateBuild($projects, $langcodes, $options) instead.
 *
 * @see https://www.drupal.org/node/3572345
 */
function locale_translation_batch_update_build($projects = [], $langcodes = [], $options = []) {
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchUpdateBuild($projects, $langcodes, $options) instead. See https://www.drupal.org/node/3572345', E_USER_DEPRECATED);
  return \Drupal::service(LocaleFetch::class)->batchUpdateBuild($projects, $langcodes, $options);
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)
 *   ->batchFetchBuild($projects, $langcodes, $options) instead.
 *
 * @see https://www.drupal.org/node/3572345
 */
function locale_translation_batch_fetch_build($projects = [], $langcodes = [], $options = []) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchFetchBuild($projects, $langcodes, $options) instead. See https://www.drupal.org/node/3572345', E_USER_DEPRECATED);
  return \Drupal::service(LocaleFetch::class)->batchFetchBuild($projects, $langcodes, $options);
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There
 *   is no replacement.
 *
 * @see https://www.drupal.org/node/3572345
 */
function _locale_translation_fetch_operations($projects, $langcodes, $options): array {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3572345', E_USER_DEPRECATED);
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
