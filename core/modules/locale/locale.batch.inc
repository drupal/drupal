<?php

/**
 * @file
 */

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Load the common translation API.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue: https://www.drupal.org/node/1834298.
require_once __DIR__ . '/locale.translation.inc';

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
function locale_translation_batch_version_check(string $project, string $langcode, array|\ArrayAccess &$context) {
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
  locale_translate_delete_translation_files([$project]);

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
function locale_translation_batch_status_check($project, $langcode, array $options, &$context) {
  $failure = $checked = FALSE;
  $options += [
    'finish_feedback' => TRUE,
    'use_remote' => TRUE,
  ];
  $source = locale_translation_get_status([$project], [$langcode]);
  $source = $source[$project][$langcode];

  // Check the status of local translation files.
  if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
    if ($file = locale_translation_source_check_file($source)) {
      locale_translation_status_save($source->name, $source->langcode, LOCALE_TRANSLATION_LOCAL, $file);
    }
    $checked = TRUE;
  }

  // Check the status of remote translation files.
  if ($options['use_remote'] && isset($source->files[LOCALE_TRANSLATION_REMOTE])) {
    $remote_file = $source->files[LOCALE_TRANSLATION_REMOTE];
    if ($result = locale_translation_http_check($remote_file->uri)) {
      // Update the file object with the result data. In case of a redirect we
      // store the resulting uri.
      if (isset($result['last_modified'])) {
        $remote_file->uri = $result['location'] ?? $remote_file->uri;
        $remote_file->timestamp = $result['last_modified'];
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
  $context['message'] = t('Checked %langcode translation for %project.', ['%langcode' => $langcode, '%project' => $source->project]);
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
function locale_translation_batch_status_finished($success, $results) {
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
 * @param object $project
 *   Source object of the translatable project.
 * @param string $langcode
 *   Language code.
 * @param array $context
 *   The batch context.
 *
 * @see locale_translation_batch_fetch_import()
 */
function locale_translation_batch_fetch_download($project, $langcode, &$context) {
  $sources = locale_translation_get_status([$project], [$langcode]);
  if (isset($sources[$project][$langcode])) {
    $source = $sources[$project][$langcode];
    if (isset($source->type) && $source->type == LOCALE_TRANSLATION_REMOTE) {
      if ($file = locale_translation_download_source($source->files[LOCALE_TRANSLATION_REMOTE], 'translations://')) {
        $context['message'] = t('Downloaded %langcode translation for %project.', ['%langcode' => $langcode, '%project' => $source->project]);
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
 * @param object $project
 *   Source object of the translatable project.
 * @param string $langcode
 *   Language code.
 * @param array $options
 *   Array of import options.
 * @param array $context
 *   The batch context.
 *
 * @see locale_translate_batch_import_files()
 * @see locale_translation_batch_fetch_download()
 */
function locale_translation_batch_fetch_import($project, $langcode, $options, &$context) {
  $sources = locale_translation_get_status([$project], [$langcode]);
  if (isset($sources[$project][$langcode])) {
    $source = $sources[$project][$langcode];
    if (isset($source->type)) {
      if ($source->type == LOCALE_TRANSLATION_REMOTE || $source->type == LOCALE_TRANSLATION_LOCAL) {
        $file = $source->files[LOCALE_TRANSLATION_LOCAL];
        \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
        $options += [
          'message' => t('Importing %langcode translation for %project.', ['%langcode' => $langcode, '%project' => $source->project]),
        ];
        // Import the translation file. For large files the batch operations is
        // progressive and will be called repeatedly until finished.
        locale_translate_batch_import($file, $options, $context);

        // The import is finished.
        if (isset($context['finished']) && $context['finished'] == 1) {
          // The import is successful.
          if (isset($context['results']['files'][$file->uri])) {
            $context['message'] = t('Imported %langcode translation for %project.', ['%langcode' => $langcode, '%project' => $source->project]);

            // Save the data of imported source into the {locale_file} table and
            // update the current translation status.
            locale_translation_status_save($project, $langcode, LOCALE_TRANSLATION_CURRENT, $source->files[LOCALE_TRANSLATION_LOCAL]);
          }
        }
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
function locale_translation_batch_fetch_finished($success, $results) {
  \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
  if ($success) {
    \Drupal::state()->set('locale.translation_last_checked', \Drupal::time()->getRequestTime());
  }
  return locale_translate_batch_finished($success, $results);
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
 */
function locale_translation_http_check($uri) {
  $logger = \Drupal::logger('locale');
  try {
    $actual_uri = NULL;
    $response = \Drupal::service('http_client_factory')->fromOptions([
      'allow_redirects' => [
        'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $request_uri) use (&$actual_uri) {
          $actual_uri = (string) $request_uri;
        },
      ],
    ])->head($uri);
    $result = [];

    // Return the effective URL if it differs from the requested.
    if ($actual_uri && $actual_uri !== $uri) {
      $result['location'] = $actual_uri;
    }

    $result['last_modified'] = $response->hasHeader('Last-Modified') ? strtotime($response->getHeaderLine('Last-Modified')) : 0;
    return $result;
  }
  catch (RequestException $e) {
    // Handle 4xx and 5xx http responses.
    if ($response = $e->getResponse()) {
      if ($response->getStatusCode() == 404) {
        // File not found occurs when a translation file is not yet available
        // at the translation server. But also if a custom module or custom
        // theme does not define the location of a translation file. By default
        // the file is checked at the translation server, but it will not be
        // found there.
        $logger->notice('Translation file not found: @uri.', ['@uri' => $uri]);
        return TRUE;
      }
      $logger->notice('HTTP request to @url failed with error: @error.', ['@url' => $uri, '@error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()]);
    }
  }
  // We need to handle ConnectException separately because in Guzzle 7 it
  // doesn't have a getResponse() method, so the above will fatal.
  catch (ConnectException $e) {
    $logger->notice('HTTP request to @url failed with error: @error.', ['@url' => $uri, '@error' => $e->getMessage()]);
  }

  return FALSE;
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
 */
function locale_translation_download_source($source_file, $directory = 'temporary://') {
  try {
    $data = (string) \Drupal::httpClient()->get($source_file->uri)->getBody();
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    $filename = $fileSystem->basename($source_file->uri);
    if ($uri = $fileSystem->saveData($data, $directory . $filename, FileExists::Replace)) {
      $file = clone($source_file);
      $file->type = LOCALE_TRANSLATION_LOCAL;
      $file->uri = $uri;
      $file->directory = $directory;
      $file->timestamp = filemtime($uri);
      return $file;
    }
  }
  catch (ClientExceptionInterface $exception) {
    \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
  }
  catch (FileException | InvalidStreamWrapperException $e) {
    \Drupal::messenger()->addError(t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
  }
  \Drupal::logger('locale')->error('Unable to download translation file @uri.', ['@uri' => $source_file->uri]);
  return FALSE;
}
