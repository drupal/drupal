<?php

/**
 * @file
 */

use Drupal\Core\Url;
use Drupal\locale\File\LocaleFile;
use Drupal\locale\File\LocaleFileManager;
use Drupal\locale\File\RemoteFileStatus;
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
 */
function locale_translation_batch_version_check(string $project, string $langcode, array|\ArrayAccess &$context): void {
  $locale_project = \Drupal::service('locale.project')->get($project);

  if (empty($locale_project)) {
    return;
  }

  $status = \Drupal::keyValue('locale.translation_status')->get($project);
  if (!isset($status[$langcode])) {
    return;
  }

  if ($locale_project['version'] == $status[$langcode]->version) {
    return;
  }

  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
  locale_translation_status_delete_projects([$project]);
  \Drupal::service(LocaleFileManager::class)->deleteTranslationFiles([$project]);

  $context['message'] = t('Checked version of %project.', ['%project' => $project]);
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
 */
function locale_translation_batch_status_check($project, $langcode, array $options, &$context): void {
  $failure = $checked = FALSE;
  $options += [
    'finish_feedback' => TRUE,
    'use_remote' => TRUE,
  ];
  $source = locale_translation_get_status([$project], [$langcode]);
  $source = $source[$project][$langcode];

  // Check the status of local translation files.
  if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
    if ($file = \Drupal::service(LocaleSource::class)->sourceCheckFile($source)) {
      locale_translation_status_save($source->name, $source->langcode, LOCALE_TRANSLATION_LOCAL, $file);
    }
    $checked = TRUE;
  }

  // Check the status of remote translation files.
  if ($options['use_remote'] && isset($source->files[LOCALE_TRANSLATION_REMOTE])) {
    $remote_file = $source->files[LOCALE_TRANSLATION_REMOTE];
    if ($langcode === 'en') {
      // drupal.org does not support english as translation.
      $uri = \Drupal::service(LocaleSource::class)->buildServerPattern($source, strtr(\Drupal::TRANSLATION_DEFAULT_SERVER_PATTERN, ['%language' => $langcode]));
      if ($uri === $remote_file->uri) {
        $failure = TRUE;
      }
    }

    $remoteFileInfo = \Drupal::service(LocaleFileManager::class)->checkRemoteFileStatus($remote_file->uri);
    if (!$failure && $remoteFileInfo->status !== RemoteFileStatus::Error) {
      // Update the file object with the result data. In case of a redirect we
      // store the resulting uri.
      if ($remoteFileInfo->lastModified) {
        $remote_file->uri = $remoteFileInfo->location ?? $remote_file->uri;
        $remote_file->timestamp = $remoteFileInfo->lastModified;
        locale_translation_status_save($source->name, $source->langcode, LOCALE_TRANSLATION_REMOTE, $remote_file);
      }
      // @todo What to do with when the file is not found (404)? To prevent
      //   re-checking within the TTL (1day, 1week) we can set a last_checked
      //   timestamp or cache the result.
      $checked = TRUE;
    }
    else {
      $failure = TRUE;
    }
  }

  // Provide user feedback and record success or failure for reporting at the
  // end of the batch.
  if ($options['finish_feedback'] && $checked) {
    $context['results']['files'][] = $source->name;
  }
  if ($failure && !$checked) {
    $context['results']['failed_files'][] = $source->name;
  }
  $context['message'] = t('Checked %langcode translation for %project.', [
    '%langcode' => $langcode,
    '%project' => $source->project,
  ]);
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
 */
function locale_translation_batch_status_finished($success, $results): void {
  if ($success) {
    if (isset($results['failed_files'])) {
      if (\Drupal::moduleHandler()->moduleExists('dblog') && \Drupal::currentUser()->hasPermission('access site reports')) {
        $message = \Drupal::translation()->formatPlural(count($results['failed_files']), 'One translation file could not be checked. <a href=":url">See the log</a> for details.', '@count translation files could not be checked. <a href=":url">See the log</a> for details.', [':url' => Url::fromRoute('dblog.overview')->toString()]);
      }
      else {
        $message = \Drupal::translation()->formatPlural(count($results['failed_files']), 'One translation files could not be checked. See the log for details.', '@count translation files could not be checked. See the log for details.');
      }
      \Drupal::messenger()->addError($message);
    }
    if (isset($results['files'])) {
      \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural(
        count($results['files']),
        'Checked available interface translation updates for one project.',
        'Checked available interface translation updates for @count projects.'
      ));
    }
    if (!isset($results['failed_files']) && !isset($results['files'])) {
      \Drupal::messenger()->addStatus(t('Nothing to check.'));
    }
    \Drupal::state()->set('locale.translation_last_checked', \Drupal::time()->getRequestTime());
  }
  else {
    \Drupal::messenger()->addError(t('An error occurred trying to check available interface translation updates.'));
  }
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
 * @see locale_translation_batch_fetch_import()
 */
function locale_translation_batch_fetch_download($project, $langcode, &$context): void {
  $sources = locale_translation_get_status([$project], [$langcode]);
  if (isset($sources[$project][$langcode])) {
    $source = $sources[$project][$langcode];
    if (isset($source->type) && $source->type == LOCALE_TRANSLATION_REMOTE) {
      if ($file = \Drupal::service(LocaleFileManager::class)->downloadTranslationSource($source->files[LOCALE_TRANSLATION_REMOTE], 'translations://')) {
        $context['message'] = t('Downloaded %langcode translation for %project.', [
          '%langcode' => $langcode,
          '%project' => $source->project,
        ]);
        locale_translation_status_save($source->name, $source->langcode, LOCALE_TRANSLATION_LOCAL, $file);
      }
      else {
        $context['results']['failed_files'][] = $source->files[LOCALE_TRANSLATION_REMOTE];
      }
    }
  }
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
 * @see locale_translate_batch_build()
 * @see locale_translation_batch_fetch_download()
 */
function locale_translation_batch_fetch_import($project, $langcode, $options, &$context): void {
  $sources = locale_translation_get_status([$project], [$langcode]);
  if (isset($sources[$project][$langcode])) {
    $source = $sources[$project][$langcode];
    if (isset($source->type)) {
      if ($source->type == LOCALE_TRANSLATION_REMOTE || $source->type == LOCALE_TRANSLATION_LOCAL) {
        $file = $source->files[LOCALE_TRANSLATION_LOCAL];
        \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
        $options += [
          'message' => t('Importing %langcode translation for %project.', [
            '%langcode' => $langcode,
            '%project' => $source->project,
          ]),
        ];
        // Import the translation file. For large files the batch operations is
        // progressive and will be called repeatedly until finished.
        locale_translate_batch_import($file, $options, $context);

        // The import is finished.
        if (isset($context['finished']) && $context['finished'] == 1) {
          // The import is successful.
          if (isset($context['results']['files'][$file->uri])) {
            $context['message'] = t('Imported %langcode translation for %project.', [
              '%langcode' => $langcode,
              '%project' => $source->project,
            ]);

            // Save the data of imported source into the {locale_file} table and
            // update the current translation status.
            locale_translation_status_save($project, $langcode, LOCALE_TRANSLATION_CURRENT, $source->files[LOCALE_TRANSLATION_LOCAL]);
          }
        }
      }
      elseif ($source->type == LOCALE_TRANSLATION_CURRENT) {
        /*
         * This can happen if the locale_translation_batch_fetch_import
         * batch was interrupted
         * and the translation was imported by another batch.
         */
        $context['message'] = t('Ignoring already imported translation for %project.', ['%project' => $source->project]);
        $context['finished'] = 1;
      }
    }
  }
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
 */
function locale_translation_batch_fetch_finished($success, $results): void {
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
  if ($success) {
    \Drupal::state()->set('locale.translation_last_checked', \Drupal::time()->getRequestTime());
  }
  locale_translate_batch_finished($success, $results);
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
