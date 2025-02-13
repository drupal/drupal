<?php

/**
 * @file
 * These must remain procedural because they test file inclusion for batch.
 */

declare(strict_types=1);

use Drupal\batch_test\BatchTestCallbacks;
use Drupal\batch_test\BatchTestHelper;

/**
 * Implements callback_batch_operation().
 *
 * Performs a simple batch operation.
 */
function _batch_test_set_file_callback_1($id, $sleep, &$context): void {
  $batch_test_helper = new BatchTestHelper();
  // No-op, but ensure the batch takes a couple iterations.
  // Batch needs time to run for the test, so sleep a bit.
  usleep($sleep);
  // Track execution, and store some result for post-processing in the
  // 'finished' callback.
  $batch_test_helper->stack("op 1 id $id");
  $context['results'][1][] = $id;
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 1.
 */
function _batch_test_set_file_finished_1($success, $results, $operations, $elapsed): void {
  $batch_test_callbacks = new BatchTestCallbacks();
  $batch_test_callbacks->finishedHelper(1, $success, $results, $operations, $elapsed);
}
