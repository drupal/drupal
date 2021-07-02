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

use Drupal\Core\Batch\Percentage;
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
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');
  $batch = &$batch_processor->getCurrentBatch();

  if (!($request_id = $request->query->get('id'))) {
    return FALSE;
  }

  // Retrieve the current state of the batch.
  if (!$batch) {
    $batch = \Drupal::service('batch.storage')->load($request_id);
    if (!$batch) {
      \Drupal::messenger()->addError(t('No active batch.'));
      return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString());
    }
  }

  // We need to store the updated batch information in the batch storage after
  // processing the batch. In order for the error page to work correctly this
  // needs to be done even in case of a PHP fatal error in which case the end of
  // this function is never reached. Therefore we register a shutdown function
  // to handle this case. Because with FastCGI and fastcgi_finish_request()
  // shutdown functions are called after the HTTP connection is closed, updating
  // the batch information in a shutdown function would lead to race conditions
  // between consecutive requests if the batch processing continues. In case of
  // a fatal error the processing stops anyway, so it works even with FastCGI.
  // However, we must ensure to only update in the shutdown phase in this
  // particular case we track whether the batch information still needs to be
  // updated.
  // @see _batch_shutdown()
  // @see \Symfony\Component\HttpFoundation\Response::send()
  drupal_register_shutdown_function('_batch_shutdown');
  _batch_needs_update(TRUE);

  $build = [];

  // Add batch-specific libraries.
  foreach ($batch['sets'] as $batch_set) {
    if (isset($batch_set['library'])) {
      foreach ($batch_set['library'] as $library) {
        $build['#attached']['library'][] = $library;
      }
    }
  }

  $op = $request->query->get('op', '');
  switch ($op) {
    case 'start':
    case 'do_nojs':
      // Display the full progress page on startup and on each additional
      // non-JavaScript iteration.
      $current_set = $batch_processor->getCurrentSet();
      $build['#title'] = $current_set['title'];
      $build['content'] = _batch_progress_page();

      $response = $build;
      break;

    case 'do':
      // JavaScript-based progress page callback.
      $response = _batch_do();
      break;

    case 'finished':
      // _batch_finished() returns a RedirectResponse.
      $response = $batch_processor->finishedProcessing();
      break;
  }

  if ($batch) {
    \Drupal::service('batch.storage')->update($batch);
  }
  _batch_needs_update(FALSE);

  return $response;
}

/**
 * Checks whether the batch information needs to be updated in the storage.
 *
 * @param bool $new_value
 *   (optional) A new value to set.
 *
 * @return bool
 *   TRUE if the batch information needs to be updated; FALSE otherwise.
 */
function _batch_needs_update($new_value = NULL) {
  $needs_update = &drupal_static(__FUNCTION__, FALSE);

  if (isset($new_value)) {
    $needs_update = $new_value;
  }

  return $needs_update;
}

/**
 * Does one execution pass with JavaScript and returns progress to the browser.
 *
 * @see _batch_progress_page_js()
 * @see \Drupal\Core\Batch\BatchProcessorInterface::processQueue()
 */
function _batch_do() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');

  // Perform actual processing.
  list($percentage, $message, $label) = $batch_processor->processQueue();

  return new JsonResponse(['status' => TRUE, 'percentage' => $percentage, 'message' => $message, 'label' => $label]);
}

/**
 * Outputs a batch processing page.
 *
 * @see \Drupal\Core\Batch\BatchProcessorInterface::processQueue()
 */
function _batch_progress_page() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');

  $batch = &$batch_processor->getCurrentBatch();

  $current_set = $batch_processor->getCurrentSet();

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
    $bare_html_page_renderer = \Drupal::service('bare_html_page_renderer');
    $response = $bare_html_page_renderer->renderBarePage(['#markup' => $fallback], $current_set['title'], 'maintenance_page', [
      '#show_messages' => FALSE,
    ]);

    // Just use the content of the response.
    $fallback = $response->getContent();

    list($fallback) = explode('<!--partial-->', $fallback);
    print $fallback;

    // Perform actual processing.
    list($percentage, $message, $label) = $batch_processor->processQueue();
    if ($percentage == 100) {
      $new_op = 'finished';
    }

    // PHP did not die; remove the fallback output.
    ob_end_clean();
  }

  // Merge required query parameters for batch processing into those provided by
  // batch_set() or hook_batch_alter().
  $query_options = $batch['url']->getOption('query');
  $query_options['id'] = $batch['id'];
  $query_options['op'] = $new_op;
  $batch['url']->setOption('query', $query_options);

  $url = $batch['url']->toString(TRUE)->getGeneratedUrl();

  $build = [
    '#theme' => 'progress_bar',
    '#percent' => $percentage,
    '#message' => ['#markup' => $message],
    '#label' => $label,
    '#attached' => [
      'html_head' => [
        [
          [
            // Redirect through a 'Refresh' meta tag if JavaScript is disabled.
            '#tag' => 'meta',
            '#noscript' => TRUE,
            '#attributes' => [
              'http-equiv' => 'Refresh',
              'content' => '0; URL=' . $url,
            ],
          ],
          'batch_progress_meta_refresh',
        ],
      ],
      // Adds JavaScript code and settings for clients where JavaScript is enabled.
      'drupalSettings' => [
        'batch' => [
          'errorMessage' => $current_set['error_message'] . '<br />' . $batch['error_message'],
          'initMessage' => $current_set['init_message'],
          'uri' => $url,
        ],
      ],
      'library' => [
        'core/drupal.batch',
      ],
    ],
  ];
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
 * @return array
 *   An array containing a completion value (in percent) and a status message.
 */
function _batch_process() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');
  return $batch_processor->processQueue();
}

/**
 * Formats the percent completion for a batch set.
 *
 * @param int $total
 *   The total number of operations.
 * @param int|float $current
 *   The number of the current operation. This may be a floating point number
 *   rather than an integer in the case of a multi-step operation that is not
 *   yet complete; in that case, the fractional part of $current represents the
 *   fraction of the operation that has been completed.
 *
 * @return string
 *   The properly formatted percentage, as a string. We output percentages
 *   using the correct number of decimal places so that we never print "100%"
 *   until we are finished, but we also never print more decimal places than
 *   are meaningful.
 *
 * @see \Drupal\Core\Batch\BatchProcessorInterface::processQueue()
 */
function _batch_api_percentage($total, $current) {
  return Percentage::format($total, $current);
}

/**
 * Returns the batch set being currently processed.
 */
function &_batch_current_set() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');
  // Only variables can be returned by reference.
  $batch_current_set = $batch_processor->getCurrentSet();
  return $batch_current_set;
}

/**
 * Retrieves the next set in a batch.
 *
 * If there is a subsequent set in this batch, assign it as the new set to
 * process and execute its form submit handler (if defined), which may add
 * further sets to this batch.
 *
 * @return true|null
 *   TRUE if a subsequent set was found in the batch; no value will be returned
 *   if no subsequent set was found.
 */
function _batch_next_set() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');
  return $batch_processor->nextSet();
}

/**
 * Ends the batch processing.
 *
 * Call the 'finished' callback of each batch set to allow custom handling of
 * the results and resolve page redirection.
 */
function _batch_finished() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');
  return $batch_processor->finishedProcessing();
}

/**
 * Shutdown function: Stores the current batch data for the next request.
 *
 * @see _batch_page()
 * @see drupal_register_shutdown_function()
 */
function _batch_shutdown() {
  /** @var \Drupal\Core\Batch\BatchProcessorInterface $batch_processor */
  $batch_processor = \Drupal::service('batch.processor');

  if (($batch = $batch_processor->getCurrentBatch()) && _batch_needs_update()) {
    \Drupal::service('batch.storage')->update($batch);
  }
}
