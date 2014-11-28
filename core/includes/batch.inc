<?php

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

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Timer;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Batch\Percentage;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Renders the batch processing page based on the current state of the batch.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request object.
 *
 * @see _batch_shutdown()
 */
function _batch_page(Request $request) {
  $batch = &batch_get();

  if (!($request_id = $request->get('id'))) {
    return FALSE;
  }

  // Retrieve the current state of the batch.
  if (!$batch) {
    $batch = \Drupal::service('batch.storage')->load($request_id);
    if (!$batch) {
      drupal_set_message(t('No active batch.'), 'error');
      return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
    }
  }
  // Restore safe strings from previous batches.
  // @todo Ensure we are not storing an excessively large string list in:
  //   https://www.drupal.org/node/2295823
  if (!empty($batch['safe_strings'])) {
    SafeMarkup::setMultiple($batch['safe_strings']);
  }
  // Register database update for the end of processing.
  drupal_register_shutdown_function('_batch_shutdown');

  $build = array();

  // Add batch-specific libraries.
  foreach ($batch['sets'] as $batch_set) {
    if (isset($batch_set['library'])) {
      foreach ($batch_set['library'] as $library) {
        $build['#attached']['library'][] = $library;
      }
    }
  }

  $op = $request->get('op', '');
  switch ($op) {
    case 'start':
    case 'do_nojs':
      // Display the full progress page on startup and on each additional
      // non-JavaScript iteration.
      $current_set = _batch_current_set();
      $build['#title'] = $current_set['title'];
      $build['content'] = _batch_progress_page();
      break;

    case 'do':
      // JavaScript-based progress page callback.
      return _batch_do();

    case 'finished':
      // _batch_finished() returns a RedirectResponse.
      return _batch_finished();
  }

  return $build;
}

/**
 * Does one execution pass with JavaScript and returns progress to the browser.
 *
 * @see _batch_progress_page_js()
 * @see _batch_process()
 */
function _batch_do() {
  // Perform actual processing.
  list($percentage, $message, $label) = _batch_process();

  return new JsonResponse(array('status' => TRUE, 'percentage' => $percentage, 'message' => $message, 'label' => $label));
}

/**
 * Outputs a batch processing page.
 *
 * @see _batch_process()
 */
function _batch_progress_page() {
  $batch = &batch_get();

  $current_set = _batch_current_set();

  $new_op = 'do_nojs';

  if (!isset($batch['running'])) {
    // This is the first page so we return some output immediately.
    $percentage       = 0;
    $message          = $current_set['init_message'];
    $label            = '';
    $batch['running'] = TRUE;
  }
  else {
    // This is one of the later requests; do some processing first.

    // Error handling: if PHP dies due to a fatal error (e.g. a nonexistent
    // function), it will output whatever is in the output buffer, followed by
    // the error message.
    ob_start();
    $fallback = $current_set['error_message'] . '<br />' . $batch['error_message'];

    // We strip the end of the page using a marker in the template, so any
    // additional HTML output by PHP shows up inside the page rather than below
    // it. While this causes invalid HTML, the same would be true if we didn't,
    // as content is not allowed to appear after </html> anyway.
    $fallback = \Drupal::service('bare_html_page_renderer')->renderBarePage(['#markup' => $fallback], $current_set['title'], 'maintenance_page', array(
      '#show_messages' => FALSE,
    ));
    list($fallback) = explode('<!--partial-->', $fallback);
    print $fallback;

    // Perform actual processing.
    list($percentage, $message, $label) = _batch_process($batch);
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

  $url = _url($batch['url'], $batch['url_options']);

  $build = array(
    '#theme' => 'progress_bar',
    '#percent' => $percentage,
    '#message' => $message,
    '#label' => $label,
    '#attached' => array(
      'html_head' => array(
        array(
          array(
            // Redirect through a 'Refresh' meta tag if JavaScript is disabled.
            '#tag' => 'meta',
            '#noscript' => TRUE,
            '#attributes' => array(
              'http-equiv' => 'Refresh',
              'content' => '0; URL=' . $url,
            ),
          ),
          'batch_progress_meta_refresh',
        ),
      ),
      // Adds JavaScript code and settings for clients where JavaScript is enabled.
      'js' => array(
        array(
          'type' => 'setting',
          'data' => array(
            'batch' => array(
              'errorMessage' => $current_set['error_message'] . '<br />' . $batch['error_message'],
              'initMessage' => $current_set['init_message'],
              'uri' => $url,
            ),
          ),
        ),
      ),
      'library' => array(
        'core/drupal.batch',
      ),
    ),
  );
  return $build;
}

/**
 * Processes sets in a batch.
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
    Timer::start('batch_processing');
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
      include_once \Drupal::root() . '/' . $current_set['file'];
    }

    $task_message = $label = '';
    // Assume a single pass operation and set the completion level to 1 by
    // default.
    $finished = 1;

    if ($item = $queue->claimItem()) {
      list($callback, $args) = $item->data;

      // Build the 'context' array and execute the function call.
      $batch_context = array(
        'sandbox'  => &$current_set['sandbox'],
        'results'  => &$current_set['results'],
        'finished' => &$finished,
        'message'  => &$task_message,
      );
      call_user_func_array($callback, array_merge($args, array(&$batch_context)));

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
    // sets (see \Drupal::service('form_submitter')), which can in turn add new
    // sets to the batch.
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
    if ($batch['progressive'] && Timer::read('batch_processing') > 1000) {
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

    // Total progress is the number of operations that have fully run plus the
    // completion level of the current operation.
    $current    = $total - $remaining + $finished;
    $percentage = _batch_api_percentage($total, $current);
    $elapsed    = isset($current_set['elapsed']) ? $current_set['elapsed'] : 0;
    $values     = array(
      '@remaining'  => $remaining,
      '@total'      => $total,
      '@current'    => floor($current),
      '@percentage' => $percentage,
      '@elapsed'    => \Drupal::service('date.formatter')->formatInterval($elapsed / 1000),
      // If possible, estimate remaining processing time.
      '@estimate'   => ($current > 0) ? \Drupal::service('date.formatter')->formatInterval(($elapsed * ($total - $current) / $current) / 1000) : '-',
    );
    $message = strtr($progress_message, $values);
    if (!empty($task_message)) {
      $label = $task_message;
    }

    return array($percentage, $message, $label);
  }
  else {
    // If we are not in progressive mode, the entire batch has been processed.
    return _batch_finished();
  }
}

/**
 * Formats the percent completion for a batch set.
 *
 * @param $total
 *   The total number of operations.
 * @param $current
 *   The number of the current operation. This may be a floating point number
 *   rather than an integer in the case of a multi-step operation that is not
 *   yet complete; in that case, the fractional part of $current represents the
 *   fraction of the operation that has been completed.
 *
 * @return
 *   The properly formatted percentage, as a string. We output percentages
 *   using the correct number of decimal places so that we never print "100%"
 *   until we are finished, but we also never print more decimal places than
 *   are meaningful.
 *
 * @see _batch_process()
 */
function _batch_api_percentage($total, $current) {
  return Percentage::format($total, $current);
}

/**
 * Returns the batch set being currently processed.
 */
function &_batch_current_set() {
  $batch = &batch_get();
  return $batch['sets'][$batch['current_set']];
}

/**
 * Retrieves the next set in a batch.
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
    if (isset($current_set['form_submit']) && ($callback = $current_set['form_submit']) && is_callable($callback)) {
      // We use our stored copies of $form and $form_state to account for
      // possible alterations by previous form submit handlers.
      $complete_form = &$batch['form_state']->getCompleteForm();
      call_user_func_array($callback, array(&$complete_form, &$batch['form_state']));
    }
    return TRUE;
  }
}

/**
 * Ends the batch processing.
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
        include_once \Drupal::root() . '/' . $batch_set['file'];
      }
      if (is_callable($batch_set['finished'])) {
        $queue = _batch_queue($batch_set);
        $operations = $queue->getAllItems();
        call_user_func_array($batch_set['finished'], array($batch_set['success'], $batch_set['results'], $operations, \Drupal::service('date.formatter')->formatInterval($batch_set['elapsed'] / 1000)));
      }
    }
  }

  // Clean up the batch table and unset the static $batch variable.
  if ($batch['progressive']) {
    \Drupal::service('batch.storage')->delete($batch['id']);
    foreach ($batch['sets'] as $batch_set) {
      if ($queue = _batch_queue($batch_set)) {
        $queue->deleteQueue();
      }
    }
    // Clean-up the session. Not needed for CLI updates.
    if (isset($_SESSION)) {
      unset($_SESSION['batches'][$batch['id']]);
      if (empty($_SESSION['batches'])) {
        unset($_SESSION['batches']);
      }
    }
  }
  $_batch = $batch;
  $batch = NULL;

  // Redirect if needed.
  if ($_batch['progressive']) {
    // Revert the 'destination' that was saved in batch_process().
    if (isset($_batch['destination'])) {
      \Drupal::request()->query->set('destination', $_batch['destination']);
    }

    // Determine the target path to redirect to.
    if (!isset($_batch['form_state'])) {
      $_batch['form_state'] = new FormState();
    }
    if ($_batch['form_state']->getRedirect() === NULL) {
      $redirect = $_batch['batch_redirect'] ?: $_batch['source_url'];
      // Any path with a scheme does not correspond to a route.
      if (!$redirect instanceof Url) {
        $options = UrlHelper::parse($redirect);
        if (parse_url($options['path'], PHP_URL_SCHEME)) {
          $redirect = Url::fromUri($options['path'], $options);
        }
        else {
          $redirect = \Drupal::pathValidator()->getUrlIfValid($options['path']);
          if (!$redirect) {
            // Stay on the same page if the redirect was invalid.
            $redirect = Url::fromRoute('<current>');
          }
          $redirect->setOptions($options);
        }
      }
      $_batch['form_state']->setRedirectUrl($redirect);
    }

    // Use \Drupal\Core\Form\FormSubmitterInterface::redirectForm() to handle
    // the redirection logic.
    $redirect = \Drupal::service('form_submitter')->redirectForm($_batch['form_state']);
    if (is_object($redirect)) {
      return $redirect;
    }

    // If no redirection happened, redirect to the originating page. In case the
    // form needs to be rebuilt, save the final $form_state for
    // \Drupal\Core\Form\FormBuilderInterface::buildForm().
    if ($_batch['form_state']->isRebuilding()) {
      $_SESSION['batch_form_state'] = $_batch['form_state'];
    }
    $callback = $_batch['redirect_callback'];
    /** @var \Drupal\Core\Url $source_url */
    $source_url = $_batch['source_url'];
    if (is_callable($callback)) {
      $callback($_batch['source_url'], array('query' => array('op' => 'finish', 'id' => $_batch['id'])));
    }
    elseif ($callback === NULL) {
      // Default to RedirectResponse objects when nothing specified.
      $url = $source_url
        ->setAbsolute()
        ->setOption('query', ['op' => 'finish', 'id' => $_batch['id']]);
      return new RedirectResponse($url->toString());
    }
  }
}

/**
 * Shutdown function: Stores the current batch data for the next request.
 *
 * @see _batch_page()
 * @see drupal_register_shutdown_function()
 */
function _batch_shutdown() {
  if ($batch = batch_get()) {
    // Update safe strings.
    // @todo Ensure we are not storing an excessively large string list in:
    //   https://www.drupal.org/node/2295823
    $batch['safe_strings'] = SafeMarkup::getAll();
    \Drupal::service('batch.storage')->update($batch);
  }
}
