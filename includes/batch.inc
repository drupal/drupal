<?php

/**
 * @file Batch processing API for processes to run in multiple HTTP requests.
 */

/**
 * State-based dispatcher for the batch processing page.
 */
function _batch_page() {
  $batch =& batch_get();

  // Retrieve the current state of batch from db.
  if (isset($_REQUEST['id']) && $data = db_result(db_query("SELECT batch FROM {batch} WHERE bid = %d AND token = '%s'", $_REQUEST['id'], drupal_get_token($_REQUEST['id'])))) {
    $batch = unserialize($data);
  }
  else {
    return FALSE;
  }

  // Register database update for end of processing.
  register_shutdown_function('_batch_shutdown');

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  $output = NULL;
  switch ($op) {
    case 'start':
      $output = _batch_start();
      break;

    case 'do':
      // JS-version AJAX callback.
      _batch_do();
      break;

    case 'do_nojs':
      // Non-JS progress page.
      $output = _batch_progress_page_nojs();
      break;

    case 'finished':
      $output = _batch_finished();
      break;
  }

  return $output;
}

/**
 * Initiate the batch processing
 */
function _batch_start() {
  // Choose between the JS and non-JS version.
  // JS-enabled users are identified through the 'has_js' cookie set in drupal.js.
  // If the user did not visit any JS enabled page during his browser session,
  // he gets the non-JS version...
  if (isset($_COOKIE['has_js']) && $_COOKIE['has_js']) {
    return _batch_progress_page_js();
  }
  else {
    return _batch_progress_page_nojs();
  }
}

/**
 * Batch processing page with JavaScript support.
 */
function _batch_progress_page_js() {
  $batch = batch_get();

  // The first batch set gets to set the page title
  // and the initialization and error messages.
  $current_set = _batch_current_set();
  drupal_set_title($current_set['title']);
  drupal_add_js('misc/progress.js', 'core', 'header', FALSE, FALSE);

  $url = url($batch['url'], array('query' => array('id' => $batch['id'])));
  $js_setting = array(
    'batch' => array(
      'errorMessage' => $current_set['error_message'] .'<br/>'. $batch['error_message'],
      'initMessage' => $current_set['init_message'],
      'uri' => $url,
    ),
  );
  drupal_add_js($js_setting, 'setting');
  drupal_add_js('misc/batch.js', 'core', 'header', FALSE, FALSE);

  $output = '<div id="progress"></div>';
  return $output;
}

/**
 * Do one pass of execution and inform back the browser about progression
 * (used for JavaScript-mode only).
 */
function _batch_do() {
  // HTTP POST required
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    drupal_set_message(t('HTTP POST is required.'), 'error');
    drupal_set_title(t('Error'));
    return '';
  }

  // Perform actual processing.
  list($percentage, $message) = _batch_process();

  drupal_json(array('status' => TRUE, 'percentage' => $percentage, 'message' => $message));
}

/**
 * Batch processing page without JavaScript support.
 */
function _batch_progress_page_nojs() {
  $batch =& batch_get();
  $current_set = _batch_current_set();

  drupal_set_title($current_set['title']);

  $new_op = 'do_nojs';

  if (!isset($batch['running'])) {
    // This is the first page so we return some output immediately.
    $percentage = 0;
    $message = $current_set['init_message'];
    $batch['running'] = TRUE;
  }
  else {
    // This is one of the later requests: do some processing first.

    // Error handling: if PHP dies due to a fatal error (e.g. non-existant
    // function), it will output whatever is in the output buffer,
    // followed by the error message.
    ob_start();
    $fallback = $current_set['error_message'] .'<br/>'. $batch['error_message'];
    drupal_maintenance_theme();
    $fallback = theme('maintenance_page', $fallback, FALSE, FALSE);

    // We strip the end of the page using a marker in the template, so any
    // additional HTML output by PHP shows up inside the page rather than
    // below it. While this causes invalid HTML, the same would be true if
    // we didn't, as content is not allowed to appear after </html> anyway.
    list($fallback) = explode('<!--partial-->', $fallback);
    print $fallback;

    // Perform actual processing.
    list($percentage, $message) = _batch_process($batch);
    if ($percentage == 100) {
      $new_op = 'finished';
    }

    // PHP did not die : remove the fallback output.
    ob_end_clean();
  }

  $url = url($batch['url'], array('query' => array('id' => $batch['id'], 'op' => $new_op)));
  drupal_set_html_head('<meta http-equiv="Refresh" content="0; URL='. $url .'">');
  $output = theme('progress_bar', $percentage, $message);
  return $output;
}

/**
 * Advance batch processing for 1 second (or process the whole batch if it
 * was not set for progressive execution - e.g forms submitted by drupal_execute).
 */
function _batch_process() {
  $batch =& batch_get();
  $current_set =& _batch_current_set();
  $set_changed = TRUE;

  if ($batch['progressive']) {
    timer_start('batch_processing');
  }

  while (!$current_set['success']) {
    // If this is the first time we iterate this batch set in the current
    // request, we check if it requires an additional file for functions
    // definitions.
    if ($set_changed && isset($current_set['file']) && is_file($current_set['file'])) {
      include_once($current_set['file']);
    }

    $finished = 1;
    $task_message = '';
    if ((list($function, $args) = reset($current_set['operations'])) && function_exists($function)) {
      // Build the 'context' array, execute the function call,
      // and retrieve the user message.
      $batch_context = array('sandbox' => &$current_set['sandbox'], 'results' => &$current_set['results'], 'finished' => &$finished, 'message' => &$task_message);
      // Process the current operation.
      call_user_func_array($function, array_merge($args, array(&$batch_context)));
    }

    if ($finished == 1) {
      // Make sure this step isn't counted double when computing $current.
      $finished = 0;
      // Remove the operation and clear the sandbox.
      array_shift($current_set['operations']);
      $current_set['sandbox'] = array();
    }

    // If the batch set is completed, browse through the remaining sets,
    // executing 'control sets' (stored form submit handlers) along the way -
    // this might in turn insert new batch sets.
    // Stop when we find a set that actually has operations.
    $set_changed = FALSE;
    $old_set = $current_set;
    while (empty($current_set['operations']) && ($current_set['success'] = TRUE) && _batch_next_set()) {
      $current_set =& _batch_current_set();
      $set_changed = TRUE;
    }
    // At this point, either $current_set is a 'real' batch set (has operations),
    // or all sets have been completed.

    // If we're in progressive mode, stop after 1 second.
    if ($batch['progressive'] && timer_read('batch_processing') > 1000) {
      break;
    }
  }

  if ($batch['progressive']) {
    // Gather progress information.

    // Reporting 100% progress will cause the whole batch to be considered
    // processed. If processing was paused right after moving to a new set,
    // we have to use the info from the new (unprocessed) one.
    if ($set_changed && isset($current_set['operations'])) {
      // Processing will continue with a fresh batch set.
      $remaining = count($current_set['operations']);
      $total = $current_set['total'];
      $progress_message = $current_set['init_message'];
      $task_message = '';
    }
    else {
      $remaining = count($old_set['operations']);
      $total = $old_set['total'];
      $progress_message = $old_set['progress_message'];
    }

    $current    = $total - $remaining + $finished;
    $percentage = $total ? floor($current / $total * 100) : 100;
    $values = array(
      '@remaining'  => $remaining,
      '@total'      => $total,
      '@current'    => floor($current),
      '@percentage' => $percentage,
      );
    $message = strtr($progress_message, $values) .'<br/>';
    $message .= $task_message ? $task_message : '&nbsp;';

    return array($percentage, $message);
  }
  else {
    // If we're not in progressive mode, the whole batch has been processed by now.
    return _batch_finished();
  }

}

/**
 * Retrieve the batch set being currently processed.
 */
function &_batch_current_set() {
  $batch =& batch_get();
  return $batch['sets'][$batch['current_set']];
}

/**
 * Move execution to the next batch set if any, executing the stored
 * form _submit handlers along the way (thus possibly inserting
 * additional batch sets).
 */
function _batch_next_set() {
  $batch =& batch_get();
  if (isset($batch['sets'][$batch['current_set'] + 1])) {
    $batch['current_set']++;
    $current_set =& _batch_current_set();
    if (isset($current_set['form_submit']) && ($function = $current_set['form_submit']) && function_exists($function)) {
      // We use our stored copies of $form and $form_state, to account for
      // possible alteration by the submit handlers.
      $function($batch['form'], $batch['form_state']);
    }
    return TRUE;
  }
}

/**
 * End the batch processing:
 * Call the 'finished' callbacks to allow custom handling of results,
 * and resolve page redirection.
 */
function _batch_finished() {
  $batch =& batch_get();

  // Execute the 'finished' callbacks for each batch set.
  foreach ($batch['sets'] as $key => $batch_set) {
    if (isset($batch_set['finished'])) {
      // Check if the set requires an additional file for functions definitions.
      if (isset($batch_set['file']) && is_file($batch_set['file'])) {
        include_once($batch_set['file']);
      }
      if (function_exists($batch_set['finished'])) {
        $batch_set['finished']($batch_set['success'], $batch_set['results'], $batch_set['operations']);
      }
    }
  }

  // Cleanup the batch table and unset the global $batch variable.
  if ($batch['progressive']) {
    db_query("DELETE FROM {batch} WHERE bid = %d", $batch['id']);
  }
  $_batch = $batch;
  $batch = NULL;

  // Redirect if needed.
  if ($_batch['progressive']) {
    // Put back the 'destination' that was saved in batch_process().
    if (isset($_batch['destination'])) {
      $_REQUEST['destination'] = $_batch['destination'];
    }

    // Use $_batch['form_state']['redirect'], or $_batch['redirect'],
    // or $_batch['source_page'].
    if (isset($_batch['form_state']['redirect'])) {
      $redirect = $_batch['form_state']['redirect'];
    }
    elseif (isset($_batch['redirect'])) {
      $redirect = $_batch['redirect'];
    }
    else {
      $redirect = $_batch['source_page'];
    }

    // Let drupal_redirect_form handle redirection logic.
    $form = isset($batch['form']) ? $batch['form'] : array();
    if (empty($_batch['form_state']['rebuild']) && empty($_batch['form_state']['storage'])) {
      drupal_redirect_form($form, $redirect);
    }

    // We get here if $form['#redirect'] was FALSE, or if the form is a
    // multi-step form. We save the final $form_state value to be retrieved
    // by drupal_get_form, and we redirect to the originating page.
    $_SESSION['batch_form_state'] = $_batch['form_state'];
    drupal_goto($_batch['source_page']);
  }
}

/**
 * Shutdown function: store the batch data for next request,
 * or clear the table if the batch is finished.
 */
function _batch_shutdown() {
  if ($batch = batch_get()) {
    db_query("UPDATE {batch} SET batch = '%s' WHERE bid = %d", serialize($batch), $batch['id']);
  }
}
