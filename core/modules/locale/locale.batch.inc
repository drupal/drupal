<?php

/**
 * @file
 */

use Drupal\locale\File\LocaleFile;
use Drupal\locale\File\LocaleFileManager;
use Drupal\locale\File\RemoteFileStatus;
use Drupal\locale\LocaleFetch;
use Drupal\locale\LocaleProjectChecker;
use Drupal\locale\LocaleSource;

/**
 * Load the common translation API.
 */

/**
 * Implements callback_batch_operation().
 *
 * Checks for changed project versions, and cleans-up data from the old version.
 * For example when a module is updated. This will make the translation import
 * system use translations that match the current version.
 *
 * @param string $project
 *   Machine name of the project for which to check the translation status.
 * @param string $langcode
 *   Language code of the language for which to check the translation.
 * @param array|\ArrayAccess $context
 *   The batch context.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->batchVersionCheck()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_version_check(string $project, string $langcode, array|\ArrayAccess &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchVersionCheck() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
  \Drupal::service(LocaleFetch::class)->batchVersionCheck($project, $langcode, $context);
}

/**
 * Implements callback_batch_operation().
 *
 * Checks the presence and creation time po translation files in located at
 * remote server location and local file system.
 *
 * @param string $project
 *   Machine name of the project for which to check the translation status.
 * @param string $langcode
 *   Language code of the language for which to check the translation.
 * @param array $options
 *   An array with options that can have the following elements:
 *   - 'finish_feedback': Whether or not to give feedback to the user when the
 *     batch is finished. Optional, defaults to TRUE.
 *   - 'use_remote': Whether or not to check the remote translation file.
 *     Optional, defaults to TRUE.
 * @param array|\ArrayAccess $context
 *   The batch context.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->batchStatusCheck()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_status_check($project, $langcode, array $options, &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchStatusCheck() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleFetch::class)->batchStatusCheck($project, $langcode, $options, $context);
}

/**
 * Implements callback_batch_finished().
 *
 * Set result message.
 *
 * @param bool $success
 *   TRUE if batch successfully completed.
 * @param array $results
 *   Batch results.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleProjectChecker::class)->batchFinished()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_status_finished($success, $results): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectChecker::class)->batchFinished() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleProjectChecker::class)->batchFinished($success, $results);
}

/**
 * Implements callback_batch_operation().
 *
 * Downloads a remote gettext file into the translations directory. When
 * successfully the translation status is updated.
 *
 * @param string $project
 *   The name of the project to import translations.
 * @param string $langcode
 *   Language code.
 * @param array $context
 *   The batch context.
 *
 * @see \Drupal\locale\LocaleFetch::batchImport()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->batchDownload()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_fetch_download($project, $langcode, &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchDownload() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::service(LocaleFetch::class)->batchDownload($project, $langcode, $context);
}

/**
 * Implements callback_batch_operation().
 *
 * Imports a gettext file from the translation directory. When successfully the
 * translation status is updated.
 *
 * @param string $project
 *   The name of the project to import translations.
 * @param string $langcode
 *   Language code.
 * @param array $options
 *   Array of import options.
 * @param array $context
 *   The batch context.
 *
 * @see \Drupal\locale\LocaleImportBatch::buildBatch()
 * @see \Drupal\locale\LocaleFetch::batchDownload()
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->batchImport()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_fetch_import($project, $langcode, $options, &$context): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchImport() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
  \Drupal::service(LocaleFetch::class)->batchImport($project, $langcode, $options, $context);
}

/**
 * Implements callback_batch_finished().
 *
 * Set result message.
 *
 * @param bool $success
 *   TRUE if batch successfully completed.
 * @param array $results
 *   Batch results.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFetch::class)->batchFinished()
 *   instead.
 *
 * @see https://www.drupal.org/node/3589759
 */
function locale_translation_batch_fetch_finished($success, $results): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFetch::class)->batchFinished() instead. See https://www.drupal.org/node/3589759', E_USER_DEPRECATED);
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
  \Drupal::service(LocaleFetch::class)->batchFinished($success, $results);
}

/**
 * Check if remote file exists and when it was last updated.
 *
 * @param string $uri
 *   URI of remote file.
 *
 * @return array|bool
 *   Associative array of file data with the following elements:
 *   - last_modified: Last modified timestamp of the translation file.
 *   - (optional) location: The location of the translation file. Is only set
 *     when a redirect (301) has occurred.
 *   TRUE if the file is not found. FALSE if a fault occurred.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFileManager::class)checkRemoteFileStatus($uri)
 *   instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translation_http_check($uri) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFileManager::class)->checkRemoteFileStatus($uri) instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  $remoteFileInfo = \Drupal::service(LocaleFileManager::class)->checkRemoteFileStatus($uri);

  if ($remoteFileInfo->status === RemoteFileStatus::Error) {
    return FALSE;
  }

  if ($remoteFileInfo->status === RemoteFileStatus::Missing) {
    return TRUE;
  }

  $result['last_modified'] = $remoteFileInfo->lastModified;
  if ($remoteFileInfo->location) {
    $result['location'] = $remoteFileInfo->location;
  }
  return $result;
}

/**
 * Downloads a translation file from a remote server.
 *
 * @param object $source_file
 *   Source file object with at least:
 *   - "uri": uri to download the file from.
 *   - "project": Project name.
 *   - "langcode": Translation language.
 *   - "version": Project version.
 *   - "filename": File name.
 * @param string $directory
 *   Directory where the downloaded file will be saved. Defaults to the
 *   temporary file path.
 *
 * @return object|false
 *   File object if download was successful. FALSE on failure.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(LocaleFileManager::class)
 *   ->downloadTranslationSource($source_file, $directory) instead.
 *
 * @see https://www.drupal.org/node/3577675
 */
function locale_translation_download_source($source_file, $directory = 'temporary://') {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleFileManager::class)->downloadTranslationSource($source_file, $directory) instead. See https://www.drupal.org/node/3577675', E_USER_DEPRECATED);
  $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $source_file->uri);
  $locale_file = new LocaleFile($source_file->filename, $source_file->uri, $hash);
  return \Drupal::service(LocaleFileManager::class)->downloadTranslationSource($locale_file, $directory);
}
