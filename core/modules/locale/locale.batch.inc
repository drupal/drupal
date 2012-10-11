<?php

/**
 * @file
 *   Batch process to check the availability of remote or local po files.
 */

/**
 * Build a batch to get the status of remote and local translation files.
 *
 * The batch process fetches the state of both remote and (if configured) local
 * translation files. The data of the most recent translation is stored per
 * per project and per language. This data is stored in a state variable
 * 'locale_translation_status'. The timestamp it was last updated is stored
 * in the state variable 'locale_translation_status_last_update'.
 *
 * @param array $sources
 *   Array of translation source objects for which to check the state of
 *   translation source files.
 */
function locale_translation_batch_status_build($sources) {
  $operations = array();

  // Set the batch processes for remote sources.
  foreach ($sources as $source) {
    $operations[] = array('locale_translation_batch_status_fetch_remote', array($source));
  }

  // Check for local sources, compare the results of local and remote and store
  // the most recent.
  $operations[] = array('locale_translation_batch_status_fetch_local', array($sources));
  $operations[] = array('locale_translation_batch_status_compare', array());

  $batch = array(
    'operations' => $operations,
    'title' => t('Checking available translations'),
    'finished' => 'locale_translation_batch_status_finished',
    'error_message' => t('Error checking available interface translation updates.'),
    'file' => drupal_get_path('module', 'locale') . '/locale.batch.inc',
  );
  return $batch;
}

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
  if (isset($source->files['remote'])) {
    $remote_file = $source->files['remote'];
    $result = locale_translation_http_check($remote_file->url);

    // Update the file object with the result data. In case of a redirect we
    // store the resulting url.
    if ($result && !empty($result->updated)) {
      $remote_file->url = isset($result->redirect_url) ? $result->redirect_url : $remote_file->url;
      $remote_file->timestamp = $result->updated;
      $source->files['remote'] = $remote_file;
    }
    $context['results'][$source->name][$source->language] = $source;
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
  module_load_include('compare.inc', 'locale');

  // Get the status of local translation files and store the result data in the
  // batch results for later processing.
  foreach ($sources as $source) {
    if (isset($source->files['local'])) {
      locale_translation_source_check_file($source);

      // If remote data was collected before, we merge it into the newly
      // collected result.
      if (isset($context['results'][$source->name][$source->language])) {
        $source->files['remote'] = $context['results'][$source->name][$source->language]->files['remote'];
      }
      $context['results'][$source->name][$source->language] = $source;
    }
  }
}

/**
 * Batch operation callback: Compare states and store the result.
 *
 * In the preceding batch processes data of remote and local translation sources
 * is collected. Here we compare the collected results and update the source
 * object with the data of the most recent translation file. The end result is
 * stored in the 'locale_translation_status' state variable. Other
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
  module_load_include('compare.inc', 'locale');
  $results = array();

  foreach ($context['results'] as $project => $langcodes) {
    foreach ($langcodes as $langcode => $source) {
      $local = isset($source->files['local']) ? $source->files['local'] : NULL;
      $remote = isset($source->files['remote']) ? $source->files['remote'] : NULL;

      // The available translation files are compare and data of the most recent
      // file is used to update the source object.
      $file = _locale_translation_source_compare($local, $remote) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $remote : $local;
      if (isset($file->timestamp)) {
        $source->type = $file->type;
        $source->timestamp = $file->timestamp;
        $results[$project][$langcode] = $source;
      }
    }
  }

  state()->set('locale_translation_status', $results);
  state()->set('locale_translation_status_last_update', REQUEST_TIME);
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
  $t = get_t();
  if($success) {
    if ($results) {
      drupal_set_message(format_plural(
        count($results),
        'Checked available interface translation updates for one project.',
        'Checked available interface translation updates for @count projects.'
      ));
    }
  }
  else {
    drupal_set_message($t('An error occurred trying to check available interface translation updates.'), 'error');
  }
}

/**
 * Check if remote file exists and when it was last updated.
 *
 * @param string $url
 *   URL of remote file.
 * @param array $headers
 *   HTTP request headers.
 * @return stdClass
 *   Result object containing the HTTP request headers, response code, headers,
 *   data, redirect status and updated timestamp.
 */
function locale_translation_http_check($url, $headers = array()) {
  $result = drupal_http_request($url, array('headers' => $headers, 'method' => 'HEAD'));
  if ($result && $result->code == '200') {
    $result->updated = isset($result->headers['last-modified']) ? strtotime($result->headers['last-modified']) : 0;
  }
  return $result;
}
