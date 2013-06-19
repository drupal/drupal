<?php

/**
 * @file
 *   Batch process to check the availability of remote or local po files.
 */

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\RequestException;

/**
 * Load the common translation API.
 */
// @todo Combine functions differently in files to avoid unnecessary includes.
// Follow-up issue http://drupal.org/node/1834298
require_once __DIR__ . '/locale.translation.inc';

/**
 * Batch operation callback: Check the availability of a remote po file.
 *
 * Checks the presence and creation time of one po file per batch process. The
 * file URL and timestamp are stored.
 *
 * @param array $source
 *   A translation source object of the project for which to check the state of
 *   a remote po file.
 * @param array $context
 *   The batch context array. The collected state is stored in the 'results'
 *   parameter of the context.
 *
 * @see locale_translation_batch_status_fetch_local()
 * @see locale_translation_batch_status_compare()
*/
function locale_translation_batch_status_fetch_remote($source, &$context) {
  // Check the translation file at the remote server and update the source
  // data with the remote status.
  if (isset($source->files[LOCALE_TRANSLATION_REMOTE])) {
    $remote_file = $source->files[LOCALE_TRANSLATION_REMOTE];
    $result = locale_translation_http_check($remote_file->uri);

    if ($result) {
      // Update the file object with the result data. In case of a redirect we
      // store the resulting uri. If a file is not found we don't update the
      // file object, and store it unchanged.
      if (isset($result['last_modified'])) {
        $remote_file->uri = isset($result['location']) ? $result['location'] : $remote_file->uri;
        $remote_file->timestamp = $result['last_modified'];
        $source->files[LOCALE_TRANSLATION_REMOTE] = $remote_file;
      }
      // Record success.
      $context['results']['files'][$source->name] = $source->name;
    }
    else {
      // An error occured when checking the file. Record the failure for
      // reporting at the end of the batch.
      $context['results']['failed_files'][] = $source->name;
    }
    $context['results']['sources'][$source->name][$source->langcode] = $source;
    $context['message'] = t('Checked translation for %project.', array('%project' => $source->project));
  }
}

/**
 * Batch operation callback: Check the availability of local po files.
 *
 * Checks the presence and creation time of po files in the local file system.
 * The file path and the timestamp are stored.
 *
 * @param array $sources
 *   Array of translation source objects of projects for which to check the
 *   state of local po files.
 * @param array $context
 *   The batch context array. The collected state is stored in the 'results'
 *   parameter of the context.
 *
 * @see locale_translation_batch_status_fetch_remote()
 * @see locale_translation_batch_status_compare()
 */
function locale_translation_batch_status_fetch_local($sources, &$context) {
  // Get the status of local translation files and store the result data in the
  // batch results for later processing.
  foreach ($sources as $source) {
    if (isset($source->files[LOCALE_TRANSLATION_LOCAL])) {
      locale_translation_source_check_file($source);

      // If remote data was collected before, we merge it into the newly
      // collected result.
      if (isset($context['results']['sources'][$source->name][$source->langcode])) {
        $source->files[LOCALE_TRANSLATION_REMOTE] = $context['results']['sources'][$source->name][$source->langcode]->files[LOCALE_TRANSLATION_REMOTE];
      }

      // Record success and store the updated source data.
      $context['results']['files'][$source->name] = $source->name;
      $context['results']['sources'][$source->name][$source->langcode] = $source;
    }
  }
  $context['message'] = t('Checked all translations.');
}

/**
 * Batch operation callback: Compare states and store the result.
 *
 * In the preceding batch processes data of remote and local translation sources
 * is collected. Here we compare the collected results and update the source
 * object with the data of the most recent translation file. The end result is
 * stored in the 'locale.translation_status' state variable. Other
 * processes can collect this data after the batch process is completed.
 *
 * @param array $context
 *   The batch context array. The 'results' element contains a structured array
 *   of project data with languages, local and remote source data.
 *
 * @see locale_translation_batch_status_fetch_remote()
 * @see locale_translation_batch_status_fetch_local()
 */
function locale_translation_batch_status_compare(&$context) {
  $history = locale_translation_get_file_history();
  $results = array();

  if (isset($context['results']['sources'])) {
    foreach ($context['results']['sources'] as $project => $langcodes) {
      foreach ($langcodes as $langcode => $source) {
        $local = isset($source->files[LOCALE_TRANSLATION_LOCAL]) ? $source->files[LOCALE_TRANSLATION_LOCAL] : NULL;
        $remote = isset($source->files[LOCALE_TRANSLATION_REMOTE]) ? $source->files[LOCALE_TRANSLATION_REMOTE] : NULL;

        // The available translation files are compared and data of the most
        // recent file is used to update the source object.
        $file = _locale_translation_source_compare($local, $remote) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $remote : $local;
        if (isset($file->timestamp)) {
          $source->type = $file->type;
          $source->timestamp = $file->timestamp;
        }

        // Compare the available translation with the current translations
        // status. If the project/language was translated before and it is more
        // recent than the most recent translation, the translation is up to
        // date. Which is marked in the source object with type "current".
        if (isset($history[$source->project][$source->langcode])) {
          $current = $history[$source->project][$source->langcode];
          // Add the current translation to the source object to save it in
          // the status cache.
          $source->files[LOCALE_TRANSLATION_CURRENT] = $current;

          if (isset($source->type)) {
            $available = $source->files[$source->type];
            $result = _locale_translation_source_compare($current, $available) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $available : $current;
            $source->type = $result->type;
            $source->timestamp = $result->timestamp;
          }
          else {
            $source->type = $current->type;
            $source->timestamp = $current->timestamp;
          }
        }

        $results[$project][$langcode] = $source;
      }
    }
    $context['message'] = t('Updated translation status.');
  }
  locale_translation_status_save($results);
}

/**
 * Batch finished callback: Set result message.
 *
 * @param boolean $success
 *   TRUE if batch succesfully completed.
 * @param array $results
 *   Batch results.
 */
function locale_translation_batch_status_finished($success, $results) {
  if ($success) {
    if (isset($results['failed_files'])) {
        if (Drupal::moduleHandler()->moduleExists('dblog')) {
          $message = format_plural(count($results['failed_files']), 'One translation file could not be checked. <a href="@url">See the log</a> for details.', '@count translation files could not be checked. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
        }
        else {
          $message = format_plural(count($results['failed_files']), 'One translation files could not be checked. See the log for details.', '@count translation files could not be checked. See the log for details.');
        }
        drupal_set_message($message, 'error');
    }
    if (isset($results['files'])) {
      drupal_set_message(format_plural(
        count($results['sources']),
        'Checked available interface translation updates for one project.',
        'Checked available interface translation updates for @count projects.'
      ));
    }
    if (!isset($results['failed_files']) && !isset($results['files'])) {
      drupal_set_message(t('Nothing to check.'));
    }
  }
  else {
    drupal_set_message(t('An error occurred trying to check available interface translation updates.'), 'error');
  }
}

/**
 * Loads translation source data for the projects to be updated.
 *
 * Source data is loaded from cache and stored in the context results array.
 * Source data contains the translations status per project / per language
 * and whether translation updates are available and where the updates can be
 * retrieved from. The data is stored in the $context['results'] parameter
 * so that other batch operations can take this data as input for their
 * operation.
 *
 * @see locale_translation_batch_fetch_download()
 * @see locale_translation_batch_fetch_import()
 * @see locale_translation_batch_fetch_update_status()
 * @see locale_translation_batch_status_compare()
 */
function locale_translation_batch_fetch_sources($projects, $langcodes, &$context) {
  $context['results']['input'] = locale_translation_load_sources($projects, $langcodes);

  // If this batch operation is preceded by the status check operations, the
  // results of those operation are stored in the context. We remove them here
  // to keep the result records clean.
  unset($context['results']['files']);
  unset($context['results']['failed_files']);
}

/**
 * Batch operation: Download a remote translation file.
 *
 * This operation downloads a remote gettext file and saves it in the temporary
 * directory. The remote file URL is taken from the input data in
 * $context['results']['input']. The result of the operation is stored in
 * $context['results']['sources'] and contains the URL of the temporary file.
 *
 * @param object $project
 *   Source object of the translatable project.
 * @param string $langcode
 *   Language code.
 * @param $context
 *   Batch context array.
 *
 * @see locale_translation_batch_fetch_sources()
 * @see locale_translation_batch_fetch_import()
 * @see locale_translation_batch_fetch_update_status()
 * @see locale_translation_batch_status_compare()
 */
function locale_translation_batch_fetch_download($project, $langcode, &$context) {
  $sources = $context['results']['input'];
  if (isset($sources[$project . ':' . $langcode])) {
    $source = $sources[$project . ':' . $langcode];
    if (isset($source->type) && $source->type == LOCALE_TRANSLATION_REMOTE) {
      if ($file = locale_translation_download_source($source->files[LOCALE_TRANSLATION_REMOTE])) {
        $context['message'] = t('Downloaded translation for %project.', array('%project' => $source->project));
        $source->files[LOCALE_TRANSLATION_DOWNLOADED] = $file;
      }
      else {
        $context['results']['failed_files'][] = $source->files[LOCALE_TRANSLATION_REMOTE];
      }
      $context['results']['sources'][$project][$langcode] = $source;
    }
  }
}

/**
 * Batch process: Import translation file.
 *
 * This batch operation imports either a local gettext file or a downloaded
 * remote gettext file. In case of a downloaded file the location of the
 * temporary file is found in the $context['results']['sources']. The temporary
 * file will be deleted after importing or will be moved to the local
 * translations directory. In case of a local file the file will just be
 * imported.
 *
 * @param object $project
 *   Source object of the translatable project.
 * @param string $langcode
 *   Language code.
 * @param array $options
 *   Array of import options.
 * @param $context
 *   Batch context array.
 *
 * @see locale_translate_batch_import_files()
 * @see locale_translation_batch_fetch_sources()
 * @see locale_translation_batch_fetch_download()
 * @see locale_translation_batch_fetch_update_status()
 * @see locale_translation_batch_status_compare()
 */
function locale_translation_batch_fetch_import($project, $langcode, $options, &$context) {
  $sources = $context['results']['input'];
  if (isset($sources[$project . ':' . $langcode])) {
    $source = $sources[$project . ':' . $langcode];
    if (isset($source->type)) {
      if ($source->type == LOCALE_TRANSLATION_REMOTE || $source->type == LOCALE_TRANSLATION_LOCAL) {

        // If we are working on a remote file we will import the downloaded
        // file. If the file was local just mark the result as such.
        if ($source->type == LOCALE_TRANSLATION_REMOTE) {
          if (isset($context['results']['sources'][$source->project][$source->langcode]->files[LOCALE_TRANSLATION_DOWNLOADED])) {
            $import_type = LOCALE_TRANSLATION_DOWNLOADED;
            $source_result = $context['results']['sources'][$source->project][$source->langcode];
          }
        }
        else {
          $import_type = LOCALE_TRANSLATION_LOCAL;
          $source_result = $source;
        }

        $file = $source_result->files[$import_type];
        module_load_include('bulk.inc', 'locale');
        $options += array(
          'message' => t('Importing translation for %project.', array('%project' => $source->project)),
        );
        // Import the translation file. For large files the batch operations is
        // progressive and will be called repeatedly untill finished.
        locale_translate_batch_import($file, $options, $context);

        // The import is finished.
        if (isset($context['finished']) && $context['finished'] == 1) {
          // The import is successfull.
          if (isset($context['results']['files'][$file->uri])) {
            $context['message'] = t('Imported translation for %project.', array('%project' => $source->project));

            // Keep the data of imported source. In the following batch
            // operation it will be saved in the {locale_file} table.
            $source_result->files[LOCALE_TRANSLATION_IMPORTED] = $source_result->files[$source->type];

            // Downloaded files are stored in the temporary files directory. If
            // files should be kept locally, they will be moved to the local
            // translations after successfull import. Otherwise the temporary
            // file is deleted after being imported.
            if ($import_type == LOCALE_TRANSLATION_DOWNLOADED && config('locale.settings')->get('translation.path') && isset($source_result->files[LOCALE_TRANSLATION_LOCAL])) {
              if (file_unmanaged_move($file->uri, $source_result->files[LOCALE_TRANSLATION_LOCAL]->uri, FILE_EXISTS_REPLACE)) {
                // The downloaded file is now moved to the local file location.
                // From this point forward we can treat it as if we imported a
                // local file.
                $import_type = LOCALE_TRANSLATION_LOCAL;
              }
            }
            // The downloaded file is imported but will not be stored locally.
            // Store the timestamp and delete the file.
            if ($import_type == LOCALE_TRANSLATION_DOWNLOADED) {
              $timestamp = filemtime($source_result->files[$import_type]->uri);
              $source_result->files[LOCALE_TRANSLATION_IMPORTED]->timestamp = $timestamp;
              $source_result->files[LOCALE_TRANSLATION_IMPORTED]->last_checked = REQUEST_TIME;
              file_unmanaged_delete($file->uri);
            }
            // If the translation file is stored in the local directory. The
            // timestamp of the file is stored.
            if ($import_type == LOCALE_TRANSLATION_LOCAL) {
              $timestamp = filemtime($source_result->files[$import_type]->uri);
              $source_result->files[LOCALE_TRANSLATION_LOCAL]->timestamp = $timestamp;
              $source_result->files[LOCALE_TRANSLATION_IMPORTED]->timestamp = $timestamp;
              $source_result->files[LOCALE_TRANSLATION_IMPORTED]->last_checked = REQUEST_TIME;

            }
          }
          else {
            // File import failed. We can delete the temporary file.
            if ($import_type == LOCALE_TRANSLATION_DOWNLOADED) {
              file_unmanaged_delete($file->uri);
            }
          }
        }
        $context['results']['sources'][$source->project][$source->langcode] = $source_result;
      }
    }
  }
}

/**
 * Batch process: Update the download history table.
 *
 * This batch process updates the {local_file} table with the data of imported
 * gettext files. Import data is taken from $context['results']['sources'].
 *
 * @param $context
 *   Batch context array.
 *
 * @see locale_translation_batch_fetch_sources()
 * @see locale_translation_batch_fetch_download()
 * @see locale_translation_batch_fetch_import()
 * @see locale_translation_batch_status_compare()
 */
function locale_translation_batch_fetch_update_status(&$context) {
  $results = array();

  if (isset($context['results']['sources'])) {
    foreach ($context['results']['sources'] as $project => $langcodes) {
      foreach ($langcodes as $langcode => $source) {

        // Store the state of the imported translations in {locale_file} table.
        // During the batch execution the data of the imported files is
        // temporary stored in $context['results']['sources']. Now it will be
        // stored in the database. Afterwards the temporary import and download
        // data can be deleted.
        if (isset($source->files[LOCALE_TRANSLATION_IMPORTED])) {
          $file = $source->files[LOCALE_TRANSLATION_IMPORTED];
          locale_translation_update_file_history($file);
          unset($source->files[LOCALE_TRANSLATION_IMPORTED]);
        }
        unset($source->files[LOCALE_TRANSLATION_DOWNLOADED]);

        // The source data is now up to date. Data of local and/or remote source
        // file is up to date including an updated time stamp. In a next batch
        // operation this can be used to update the translation status.
        $context['results']['sources'][$project][$langcode] = $source;
      }
    }
    $context['message'] = t('Updated translations.');

    // The file history has changed, flush the static cache now.
    drupal_static_reset('locale_translation_get_file_history');
  }
}

/**
 * Batch finished callback: Set result message.
 *
 * @param boolean $success
 *   TRUE if batch succesfully completed.
 * @param array
 *   Batch results.
 */
function locale_translation_batch_fetch_finished($success, $results) {
  module_load_include('bulk.inc', 'locale');
  return locale_translate_batch_finished($success, $results);
}

/**
 * Check if remote file exists and when it was last updated.
 *
 * @param string $uri
 *   URI of remote file.
 *
 * @return array|boolean
 *   Associative array of file data with the following elements:
 *   - last_modified: Last modified timestamp of the translation file.
 *   - (optional) location: The location of the translation file. Is only set
 *     when a redirect (301) has occurred.
 *   TRUE if the file is not found. FALSE if a fault occurred.
 */
function locale_translation_http_check($uri) {

  try {
    $response = Drupal::httpClient()
      ->head($uri)
      ->send();
    $result = array();

    // In case of a permanent redirected response, return the final location.
    if ($previous = $response->getPreviousResponse()) {
      if ($previous->getStatusCode() == 301) {
        $result['location'] = $previous->getLocation();
      }
    }

    $result['last_modified'] = $response->getLastModified() ? strtotime($response->getLastModified()) : 0;
    return $result;
  }
  catch (BadResponseException $e) {
    // Handle 4xx and 5xx http responses.
    $response = $e->getResponse();
    if ($response->getStatusCode() == 404) {
      // File not found occurs when a translation file is not yet available
      // at the translation server. But also if a custom module or custom
      // theme does not define the location of a translation file. By default
      // the file is checked at the translation server, but it will not be
      // found there.
      watchdog('locale', 'Translation file not found: @uri.', array('@uri' => $uri));
      return TRUE;
    }
    watchdog('locale', 'HTTP request to @url failed with error: @error.', array('@url' => $uri, '@error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()));
  }
  catch (RequestException $e) {
    // Handle connection problems and cURL specific errors (CurlException) and
    // other http related errors.
    watchdog('locale', 'HTTP request to @url failed with error: @error.', array('@url' => $uri, '@error' => $e->getMessage()));
  }
  return FALSE;
}

/**
 * Downloads source file from a remote server.
 *
 * The downloaded file is stored in the temporary files directory.
 *
 * @param object $source_file
 *   Source file object with at least:
 *   - "uri": uri to download the file from.
 *   - "project": Project name.
 *   - "langcode": Translation language.
 *   - "version": Project version.
 *   - "filename": File name.
 *
 * @return object
 *   File object if download was successful. FALSE on failure.
 */
function locale_translation_download_source($source_file) {
  if ($uri = system_retrieve_file($source_file->uri, 'temporary://')) {
    $file = new stdClass();
    $file->project = $source_file->project;
    $file->langcode = $source_file->langcode;
    $file->version = $source_file->version;
    $file->type = LOCALE_TRANSLATION_DOWNLOADED;
    $file->uri = $uri;
    $file->filename = $source_file->filename;
    return $file;
  }
  watchdog('locale', 'Unable to download translation file @uri.', array('@uri' => $source->files[LOCALE_TRANSLATION_REMOTE]->uri), WATCHDOG_ERROR);
  return FALSE;
}
