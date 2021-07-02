<?php

namespace Drupal\Core\Batch;

use Drupal\Core\Url;

/**
 * Defines a common interface for batch processors.
 */
interface BatchProcessorInterface {

  /**
   * Places the batch in the queue to be processed.
   *
   * Batch operations are added as new batch sets. Batch sets are used to spread
   * processing (primarily, but not exclusively, forms processing) over several
   * page requests. This helps to ensure that the processing is not interrupted
   * due to PHP timeouts, while users are still able to receive feedback on the
   * progress of the ongoing operations. Combining related operations into
   * distinct batch sets provides clean code independence for each batch set,
   * ensuring that two or more batches, submitted independently, can be
   * processed without mutual interference. Each batch set may specify its own
   * set of operations and results, produce its own UI messages, and trigger
   * its own 'finished' callback. Batch sets are processed sequentially, with
   * the progress bar starting afresh for each new set.
   *
   * @param array $batch_definition
   *   An associative array defining the batch. The array can be built by using
   *   toArray() method of a populated \Drupal\Core\Batch\BatchBuilder object.
   */
  public function queue($batch_definition);

  /**
   * Returns a queue object for a batch set.
   *
   * @param array $batch_set
   *   The batch set.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue object.
   */
  public function getQueue(array $batch_set);

  /**
   * Populates a job queue with the operations of a batch set.
   *
   * Depending on whether the batch is progressive or not, the
   * Drupal\Core\Queue\Batch or Drupal\Core\Queue\BatchMemory handler classes
   * will be used. The name and class of the queue are added by reference to the
   * batch set.
   *
   * @param array $batch
   *   The batch array.
   * @param string $set_id
   *   The id of the set to process.
   *
   * @internal
   */
  public function queuePopulate(array &$batch, $set_id);

  /**
   * Processes the batch.
   *
   * This function is generally not needed in form submit handlers;
   * Form API takes care of batches that were set during form submission.
   *
   * @param \Drupal\Core\Url|string $redirect
   *   (optional) Either path or Url object to redirect to when the batch has
   *   finished processing. Note that to simply force a batch to (conditionally)
   *   redirect to a custom location after it is finished processing but to
   *   otherwise allow the standard form API batch handling to occur, it is not
   *   necessary to call batch_process() and use this parameter. Instead, make
   *   the batch 'finished' callback return an instance of
   *   \Symfony\Component\HttpFoundation\RedirectResponse, which will be used
   *   automatically by the standard batch processing pipeline (and which takes
   *   precedence over this parameter).
   * @param \Drupal\Core\Url $url
   *   (optional) URL of the batch processing page.
   *   Should only be used for separate scripts like update.php.
   * @param string $redirect_callback
   *   (optional) Specify a function to be called to redirect to the progressive
   *   processing page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value
   *   otherwise.
   */
  public function process($redirect = NULL, Url $url = NULL, $redirect_callback = NULL);

  /**
   * Retrieves the current batch.
   */
  public function &getCurrentBatch();

  /**
   * Returns a queue object for a batch set.
   *
   * @param array $batch
   *   The batch set.
   *
   * @return \Drupal\Core\Queue\QueueInterface|null
   *   The queue object or null if the queue cannot be created.
   *
   * @internal
   */
  public function getQueueForBatch($batch);

  /**
   * Returns the batch set being currently processed.
   *
   * @return array
   *   The current batch set.
   *
   * @internal
   */
  public function getCurrentSet();

  /**
   * Retrieves the next set in a batch.
   *
   * If there is a subsequent set in this batch, assign it as the new set to
   * process and execute its form submit handler (if defined), which may add
   * further sets to this batch.
   *
   * @return bool
   *   TRUE if a subsequent set was found in the batch; FALSE will be returned
   *   if no subsequent set was found.
   *
   * @internal
   */
  public function nextSet();

  /**
   * Processes sets in a batch.
   *
   * If the batch was marked for progressive execution (default), this executes
   * as many operations in batch sets until an execution time of 1 second has
   * been exceeded. It will continue with the next operation of the same batch
   * set in the next request.
   *
   * @return array
   *   An array containing a completion value (in percent) and a status message.
   *
   * @internal
   */
  public function processQueue();

  /**
   * Ends the batch processing.
   *
   * Call the 'finished' callback of each batch set to allow custom handling of
   * the results and resolve page redirection.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response to the completed page or NULL to stay at the current
   *   URL.
   *
   * @internal
   */
  public function finishedProcessing();

  /**
   * Shutdown function: Stores the current batch data for the next request.
   *
   * @see _batch_page()
   * @see drupal_register_shutdown_function()
   */
  public function shutdown();

}
