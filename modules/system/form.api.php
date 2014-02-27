<?php

/**
 * @file
 * Callbacks provided by the form system.
 */

/**
 * @addtogroup callbacks
 * @{
 */

/**
 * Perform a single batch operation.
 *
 * Callback for batch_set().
 *
 * @param $MULTIPLE_PARAMS
 *   Additional parameters specific to the batch. These are specified in the
 *   array passed to batch_set().
 * @param $context
 *   The batch context array, passed by reference. This contains the following
 *   properties:
 *   - 'finished': A float number between 0 and 1 informing the processing
 *     engine of the completion level for the operation. 1 (or no value
 *     explicitly set) means the operation is finished: the operation will not
 *     be called again, and execution passes to the next operation or the
 *     callback_batch_finished() implementation. Any other value causes this
 *     operation to be called again; however it should be noted that the value
 *     set here does not persist between executions of this callback: each time
 *     it is set to 1 by default by the batch system.
 *   - 'sandbox': This may be used by operations to persist data between
 *     successive calls to the current operation. Any values set in
 *     $context['sandbox'] will be there the next time this function is called
 *     for the current operation. For example, an operation may wish to store a
 *     pointer in a file or an offset for a large query. The 'sandbox' array key
 *     is not initially set when this callback is first called, which makes it
 *     useful for determining whether it is the first call of the callback or
 *     not:
 *     @code
 *       if (empty($context['sandbox'])) {
 *         // Perform set-up steps here.
 *       }
 *     @endcode
 *     The values in the sandbox are stored and updated in the database between
 *     http requests until the batch finishes processing. This avoids problems
 *     if the user navigates away from the page before the batch finishes.
 *   - 'message': A text message displayed in the progress page.
 *   - 'results': The array of results gathered so far by the batch processing.
 *     This array is highly useful for passing data between operations. After
 *     all operations have finished, this is passed to callback_batch_finished()
 *     where results may be referenced to display information to the end-user,
 *     such as how many total items were processed.
 */
function callback_batch_operation($MULTIPLE_PARAMS, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['max'] = db_query('SELECT COUNT(DISTINCT nid) FROM {node}')->fetchField();
  }

  // For this example, we decide that we can safely process
  // 5 nodes at a time without a timeout.
  $limit = 5;

  // With each pass through the callback, retrieve the next group of nids.
  $result = db_query_range("SELECT nid FROM {node} WHERE nid > %d ORDER BY nid ASC", $context['sandbox']['current_node'], 0, $limit);
  while ($row = db_fetch_array($result)) {

    // Here we actually perform our processing on the current node.
    $node = node_load($row['nid'], NULL, TRUE);
    $node->value1 = $options1;
    $node->value2 = $options2;
    node_save($node);

    // Store some result for post-processing in the finished callback.
    $context['results'][] = check_plain($node->title);

    // Update our progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_node'] = $node->nid;
    $context['message'] = t('Now processing %node', array('%node' => $node->title));
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Complete a batch process.
 *
 * Callback for batch_set().
 *
 * This callback may be specified in a batch to perform clean-up operations, or
 * to analyze the results of the batch operations.
 *
 * @param $success
 *   A boolean indicating whether the batch has completed successfully.
 * @param $results
 *   The value set in $context['results'] by callback_batch_operation().
 * @param $operations
 *   If $success is FALSE, contains the operations that remained unprocessed.
 */
function callback_batch_finished($success, $results, $operations) {
  if ($success) {
    // Here we do something meaningful with the results.
    $message = t("!count items were processed.", array(
      '!count' => count($results),
      ));
    $message .= theme('item_list', array('items' => $results));
    drupal_set_message($message);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', array(
      '%error_operation' => $error_operation[0],
      '@arguments' => print_r($error_operation[1], TRUE)
    ));
    drupal_set_message($message, 'error');
  }
}
