<?php
// $Id: batch.inc,v 1.54 2010/11/30 17:16:37 dries Exp $


/**
 * @file
 * Batch processing API for processes to run in multiple HTTP requests.
 *
 * Note that batches are usually invoked by form submissions, which is
 * why the core interaction functions of the batch processing API live in
 * form.inc.
 *
 * @see form.inc
 * @see batch_set()
 * @see batch_process()
 * @see batch_get()
 */

/**
 * Loads a batch from the database.
 *
 * @param $id
 *   The ID of the batch to load. When a progressive batch is being processed,
 *   the relevant ID is found in $_REQUEST['id'].
 * @return
 *   An array representing the batch, or FALSE if no batch was found.
 */
function batch_load($id) {
  $batch = db_query("SELECT batch FROM {batch} WHERE bid = :bid AND token = :token", array(
    ':bid' => $id,
    ':token' => drupal_get_token($id),
  ))->fetchField();
  if ($batch) {
    return unserialize($batch);
  }
  return FALSE;
}

/**
 * State-based dispatcher for the batch processing page.
 *
 * @see _batch_shutdown()
 */
function _batch_page() {
  $batch = &batch_get();

  if (!isset($_REQUEST['id'])) {
    return FALSE;
  }

  // Retrieve the current state of the batch.
  if (!$batch) {
    $batch = batch_load($_REQUEST['id']);
    if (!$batch) {
      drupal_set_message(t('No active batch.'), 'error');
      drupal_goto();
    }
  }

  // Register database update for the end of processing.
  drupal_register_shutdown_function('_batch_shutdown');

  // Add batch-specific CSS.
  foreach ($batch['sets'] as $batch_set) {
    if (isset($batch_set['css'])) {
      foreach ($batch_set['css'] as $css) {
        drupal_add_css($css);
      }
    }
  }

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  $output = NULL;
  switch ($op) {
    case 'start':
      $output = _batch_start();
      break;

    case 'do':
      // JavaScript-based progress page callback.
      _batch_do();
      break;

    case 'do_nojs':
      // Non-JavaScript-based progress page.
      $output = _batch_progress_page_nojs();
      break;

    case 'finished':
      $output = _batch_finished();
      break;
  }

  return $output;
}

/**
 * Initialize the batch processing.
 *
 * JavaScript-enabled clients are identified by the 'has_js' cookie set in
 * drupal.js. If no JavaScript-enabled page has been visited during the current
 * user's browser session, the non-JavaScript version is returned.
 */
function _batch_start() {
  if (isset($_COOKIE['has_js']) && $_COOKIE['has_js']) {
    return _batch_progress_page_js();
  }
  else {
    return _batch_progress_page_nojs();
  }
}

/**
 * Output a batch processing page with JavaScript support.
 *
 * This initializes the batch and error messages. Note that in JavaScript-based
 * processing, the batch processing page is displayed only once and updated via
 * AHAH requests, so only the first batch set gets to define the page title.
 * Titles specified by subsequent batch sets are not displayed.
 *
 * @see batch_set()
 * @see _batch_do()
 */
function _batch_progress_page_js() {
  $batch = batch_get();

  $current_set = _batch_current_set();
  drupal_set_title($current_set['title'], PASS_THROUGH);

  // Merge required query parameters for batch processing into those provided by
  // batch_set() or hook_batch_alter().
  $batch['url_options']['query']['id'] = $batch['id'];

  $js_setting = array(
    'batch' => array(
      'errorMessage' => $current_set['error_message'] . '<br />' . $batch['error_message'],
      'initMessage' => $current_set['init_message'],
      'uri' => url($batch['url'], $batch['url_options']),
    ),
  );
  drupal_add_js($js_setting, 'setting');
  drupal_add_library('system', 'drupal.batch');

  return '<div id="progress"></div>';
}

/**
 * Do one execution pass in JavaScript-mode and return progress to the browser.
 *
 * @see _batch_progress_page_js()
 * @see _batch_process()
 */
function _batch_do() {
  // HTTP POST required.
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    drupal_set_message(t('HTTP POST is required.'), 'error');
    drupal_set_title(t('Error'));
    return '';
  }

  // Perform actual processing.
  list($percentage, $message) = _batch_process();

  drupal_json_output(array('status' => TRUE, 'percentage' => $percentage, 'message' => $message));
}

/**
 * Output a batch processing page without JavaScript support.
 *
 * @see _batch_process()
 */
function _batch_progress_page_nojs() {
  $batch = &batch_get();

  $current_set = _batch_current_set();
  drupal_set_title($current_set['title'], PASS_THROUGH);

  $new_op = 'do_nojs';

  if (!isset($batch['running'])) {
    // This is the first page so we return some output immediately.
    $percentage       = 0;
    $message          = $current_set['init_message'];
    $batch['running'] = TRUE;
  }
  else {
    // This is one of the later requests; do some processing first.

    // Error handling: if PHP dies due to a fatal error (e.g. a nonexistent
    // function), it will output whatever is in the output buffer, followed by
    // the error message.
    ob_start();
    $fallback = $current_set['error_message'] . '<br />' . $batch['error_message'];
    $fallback = theme('maintenance_page', array('content' => $fallback, 'show_messages' => FALSE));

    // We strip the end of the page using a marker in the template, so any
    // additional HTML output by PHP shows up inside the page rather than below
    // it. While this causes invalid HTML, the same would be true if we didn't,
    // as content is not allowed to appear after </html> anyway.
    list($fallback) = explode('<!--partial-->', $fallback);
    print $fallback;

    // Perform actual processing.
    list($percentage, $message) = _batch_process($batch);
    if ($percentage == 100) {
      $new_op = 'finished';
    }

    // PHP did not die; remove the fallback output.
    ob_end_clean();
  }

  // Merge required query parameters for batch processing into those provided by
  // batch_set() or hook_batch_alter().
  $batch['url_options']['query']['id'] = $batch['id'];
  $batch['url_options']['query']['op'] = $new_op;

  $url = url($batch['url'], $batch['url_options']);
  $element = array(
    '#tag' => 'meta',
    '#attributes' => array(
      'http-equiv' => 'Refresh',
      'content' => '0; URL=' . $url,
    ),
  );
  drupal_add_html_head($element, 'batch_progress_meta_refresh');

  return theme('progress_bar', array('percent' => $percentage, 'message' => $message));
}

/**
 * Process sets in a batch.
 *
 * If the batch was marked for progressive execution (default), this executes as
 * many operations in batch sets until an execution time of 1 second has been
 * exceeded. It will continue with the next operation of the same batch set in
 * the next request.
 *
 * @return
 *   An array containing a completion value (in percent) and a status message.
 */
function _batch_process() {
  $batch       = &batch_get();
  $current_set = &_batch_current_set();
  // Indicate that this batch set needs to be initialized.
  $set_changed = TRUE;

  // If this batch was marked for progressive execution (e.g. forms submitted by
  // drupal_form_submit()), initialize a timer to determine whether we need to
  // proceed with the same batch phase when a processing time of 1 second has
  // been exceeded.
  if ($batch['progressive']) {
    timer_start('batch_processing');
  }

  if (empty($current_set['start'])) {
    $current_set['start'] = microtime(TRUE);
  }

  $queue = _batch_queue($current_set);

  while (!$current_set['success']) {
    // If this is the first time we iterate this batch set in the current
    // request, we check if it requires an additional file for functions
    // definitions.
    if ($set_changed && isset($current_set['file']) && is_file($current_set['file'])) {
      include_once DRUPAL_ROOT . '/' . $current_set['file'];
    }

    $task_message = '';
    // Assume a single pass operation and set the completion level to 1 by
    // default.
    $finished = 1;

    if ($item = $queue->claimItem()) {
      list($function, $args) = $item->data;

      // Build the 'context' array and execute the function call.
      $batch_context = array(
        'sandbox'  => &$current_set['sandbox'],
        'results'  => &$current_set['results'],
        'finished' => &$finished,
        'message'  => &$task_message,
      );
      call_user_func_array($function, array_merge($args, array(&$batch_context)));

      if ($finished >= 1) {
        // Make sure this step is not counted twice when computing $current.
        $finished = 0;
        // Remove the processed operation and clear the sandbox.
        $queue->deleteItem($item);
        $current_set['count']--;
        $current_set['sandbox'] = array();
      }
    }

    // When all operations in the current batch set are completed, browse
    // through the remaining sets, marking them 'successfully processed'
    // along the way, until we find a set that contains operations.
    // _batch_next_set() executes form submit handlers stored in 'control'
    // sets (see form_execute_handlers()), which can in turn add new sets to
    // the batch.
    $set_changed = FALSE;
    $old_set = $current_set;
    while (empty($current_set['count']) && ($current_set['success'] = TRUE) && _batch_next_set()) {
      $current_set = &_batch_current_set();
      $current_set['start'] = microtime(TRUE);
      $set_changed = TRUE;
    }

    // At this point, either $current_set contains operations that need to be
    // processed or all sets have been completed.
    $queue = _batch_queue($current_set);

    // If we are in progressive mode, break processing after 1 second.
    if ($batch['progressive'] && timer_read('batch_processing') > 1000) {
      // Record elapsed wall clock time.
      $current_set['elapsed'] = round((microtime(TRUE) - $current_set['start']) * 1000, 2);
      break;
    }
  }

  if ($batch['progressive']) {
    // Gather progress information.

    // Reporting 100% progress will cause the whole batch to be considered
    // processed. If processing was paused right after moving to a new set,
    // we have to use the info from the new (unprocessed) set.
    if ($set_changed && isset($current_set['queue'])) {
      // Processing will continue with a fresh batch set.
      $remaining        = $current_set['count'];
      $total            = $current_set['total'];
      $progress_message = $current_set['init_message'];
      $task_message     = '';
    }
    else {
      // Processing will continue with the current batch set.
      $remaining        = $old_set['count'];
      $total            = $old_set['total'];
      $progress_message = $old_set['progress_message'];
    }

    $current    = $total - $remaining + $finished;
    $percentage = _batch_api_percentage($total, $current);
    $elapsed    = isset($current_set['elapsed']) ? $current_set['elapsed'] : 0;
    $values     = array(
      '@remaining'  => $remaining,
      '@total'      => $total,
      '@current'    => floor($current),
      '@percentage' => $percentage,
      '@elapsed'    => format_interval($elapsed / 1000),
      // If possible, estimate remaining processing time.
      '@estimate'   => ($current > 0) ? format_interval(($elapsed * ($total - $current) / $current) / 1000) : '-',
    );
    $message = strtr($progress_message, $values);
    if (!empty($message)) {
      $message .= '<br />';
    }
    if (!empty($task_message)) {
      $message .= $task_message;
    }

    return array($percentage, $message);
  }
  else {
    // If we are not in progressive mode, the entire batch has been processed.
    return _batch_finished();
  }
}

/**
 * Helper function for _batch_process(): returns the formatted percentage.
 *
 * @param $total
 *   The total number of operations.
 * @param $current
 *   The number of the current operation.
 * @return
 *   The properly formatted percentage, as a string. We output percentages
 *   using the correct number of decimal places so that we never print "100%"
 *   until we are finished, but we also never print more decimal places than
 *   are meaningful.
 */
function _batch_api_percentage($total, $current) {
  if (!$total || $total == $current) {
    // If $total doesn't evaluate as true or is equal to the current set, then
    // we're finished, and we can return "100".
    $percentage = "100";
  }
  else {
    // We add a new digit at 200, 2000, etc. (since, for example, 199/200
    // would round up to 100% if we didn't).
    $decimal_places = max(0, floor(log10($total / 2.0)) - 1);
    $percentage = sprintf('%01.' . $decimal_places . 'f', round($current / $total * 100, $decimal_places));
  }
  return $percentage;
}

/**
 * Return the batch set being currently processed.
 */
function &_batch_current_set() {
  $batch = &batch_get();
  return $batch['sets'][$batch['current_set']];
}

/**
 * Retrieve the next set in a batch.
 *
 * If there is a subsequent set in this batch, assign it as the new set to
 * process and execute its form submit handler (if defined), which may add
 * further sets to this batch.
 *
 * @return
 *   TRUE if a subsequent set was found in the batch.
 */
function _batch_next_set() {
  $batch = &batch_get();
  if (isset($batch['sets'][$batch['current_set'] + 1])) {
    $batch['current_set']++;
    $current_set = &_batch_current_set();
    if (isset($current_set['form_submit']) && ($function = $current_set['form_submit']) && function_exists($function)) {
      // We use our stored copies of $form and $form_state to account for
      // possible alterations by previous form submit handlers.
      $function($batch['form_state']['complete form'], $batch['form_state']);
    }
    return TRUE;
  }
}

/**
 * End the batch processing.
 *
 * Call the 'finished' callback of each batch set to allow custom handling of
 * the results and resolve page redirection.
 */
function _batch_finished() {
  $batch = &batch_get();

  // Execute the 'finished' callbacks for each batch set, if defined.
  foreach ($batch['sets'] as $batch_set) {
    if (isset($batch_set['finished'])) {
      // Check if the set requires an additional file for function definitions.
      if (isset($batch_set['file']) && is_file($batch_set['file'])) {
        include_once DRUPAL_ROOT . '/' . $batch_set['file'];
      }
      if (function_exists($batch_set['finished'])) {
        $queue = _batch_queue($batch_set);
        $operations = $queue->getAllItems();
        $batch_set['finished']($batch_set['success'], $batch_set['results'], $operations, format_interval($batch_set['elapsed'] / 1000));
      }
    }
  }

  // Clean up the batch table and unset the static $batch variable.
  if ($batch['progressive']) {
    db_delete('batch')
      ->condition('bid', $batch['id'])
      ->execute();
    foreach ($batch['sets'] as $batch_set) {
      if ($queue = _batch_queue($batch_set)) {
        $queue->deleteQueue();
      }
    }
  }
  $_batch = $batch;
  $batch = NULL;

  // Clean-up the session. Not needed for CLI updates.
  if (isset($_SESSION)) {
    unset($_SESSION['batches'][$batch['id']]);
    if (empty($_SESSION['batches'])) {
      unset($_SESSION['batches']);
    }
  }

  // Redirect if needed.
  if ($_batch['progressive']) {
    // Revert the 'destination' that was saved in batch_process().
    if (isset($_batch['destination'])) {
      $_GET['destination'] = $_batch['destination'];
    }

    // Determine the target path to redirect to.
    if (!isset($_batch['form_state']['redirect'])) {
      if (isset($_batch['redirect'])) {
        $_batch['form_state']['redirect'] = $_batch['redirect'];
      }
      else {
        $_batch['form_state']['redirect'] = $_batch['source_url'];
      }
    }

    // Use drupal_redirect_form() to handle the redirection logic.
    drupal_redirect_form($_batch['form_state']);

    // If no redirection happened, redirect to the originating page. In case the
    // form needs to be rebuilt, save the final $form_state for
    // drupal_build_form().
    if (!empty($_batch['form_state']['rebuild'])) {
      $_SESSION['batch_form_state'] = $_batch['form_state'];
    }
    $function = $_batch['redirect_callback'];
    if (function_exists($function)) {
      $function($_batch['source_url'], array('query' => array('op' => 'finish', 'id' => $_batch['id'])));
    }
  }
}

/**
 * Shutdown function; store the current batch data for the next request.
 */
function _batch_shutdown() {
  if ($batch = batch_get()) {
    db_update('batch')
      ->fields(array('batch' => serialize($batch)))
      ->condition('bid', $batch['id'])
      ->execute();
  }
}

