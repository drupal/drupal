<?php

/**
 * @file
 * Batch callbacks for the Batch API tests.
 */

use Drupal\Component\Utility\SafeMarkup;

/**
 * Implements callback_batch_operation().
 *
 * Performs a simple batch operation.
 */
function _batch_test_callback_1($id, $sleep, &$context) {
  // No-op, but ensure the batch take a couple iterations.
  // Batch needs time to run for the test, so sleep a bit.
  usleep($sleep);
  // Track execution, and store some result for post-processing in the
  // 'finished' callback.
  batch_test_stack("op 1 id $id");
  $context['results'][1][] = $id;
}

/**
 * Implements callback_batch_operation().
 *
 * Performs a multistep batch operation.
 */
function _batch_test_callback_2($start, $total, $sleep, &$context) {
  // Initialize context with progress information.
  if (!isset($context['sandbox']['current'])) {
    $context['sandbox']['current'] = $start;
    $context['sandbox']['count'] = 0;
  }

  // Process by groups of 5 (arbitrary value).
  $limit = 5;
  for ($i = 0; $i < $limit && $context['sandbox']['count'] < $total; $i++) {
    // No-op, but ensure the batch take a couple iterations.
    // Batch needs time to run for the test, so sleep a bit.
    usleep($sleep);
    // Track execution, and store some result for post-processing in the
    // 'finished' callback.
    $id = $context['sandbox']['current'] + $i;
    batch_test_stack("op 2 id $id");
    $context['results'][2][] = $id;

    // Update progress information.
    $context['sandbox']['count']++;
  }
  $context['sandbox']['current'] += $i;

  // Inform batch engine about progress.
  if ($context['sandbox']['count'] != $total) {
    $context['finished'] = $context['sandbox']['count'] / $total;
  }
}

/**
 * Implements callback_batch_operation().
 */
function _batch_test_callback_5($id, $sleep, &$context) {
  // No-op, but ensure the batch take a couple iterations.
  // Batch needs time to run for the test, so sleep a bit.
  usleep($sleep);
  // Track execution, and store some result for post-processing in the
  // 'finished' callback.
  batch_test_stack("op 5 id $id");
  $context['results'][5][] = $id;
  // This test is to test finished > 1
  $context['finished'] = 3.14;
}

/**
 * Implements callback_batch_operation().
 *
 * Performs a batch operation setting up its own batch.
 */
function _batch_test_nested_batch_callback() {
  batch_test_stack('setting up batch 2');
  batch_set(_batch_test_batch_2());
}

/**
 * Provides a common 'finished' callback for batches 1 to 4.
 */
function _batch_test_finished_helper($batch_id, $success, $results, $operations) {
  $messages = array("results for batch $batch_id");
  if ($results) {
    foreach ($results as $op => $op_results) {
      $messages[] = 'op '. SafeMarkup::escape($op) . ': processed ' . count($op_results) . ' elements';
    }
  }
  else {
    $messages[] = 'none';
  }

  if (!$success) {
    // A fatal error occurred during the processing.
    $error_operation = reset($operations);
    $messages[] = t('An error occurred while processing @op with arguments:<br />@args', array('@op' => $error_operation[0], '@args' => print_r($error_operation[1], TRUE)));
  }

  drupal_set_message(SafeMarkup::set(implode('<br>', $messages)));
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 0.
 */
function _batch_test_finished_0($success, $results, $operations) {
  _batch_test_finished_helper(0, $success, $results, $operations);
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 1.
 */
function _batch_test_finished_1($success, $results, $operations) {
  _batch_test_finished_helper(1, $success, $results, $operations);
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 2.
 */
function _batch_test_finished_2($success, $results, $operations) {
  _batch_test_finished_helper(2, $success, $results, $operations);
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 3.
 */
function _batch_test_finished_3($success, $results, $operations) {
  _batch_test_finished_helper(3, $success, $results, $operations);
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 4.
 */
function _batch_test_finished_4($success, $results, $operations) {
  _batch_test_finished_helper(4, $success, $results, $operations);
}

/**
 * Implements callback_batch_finished().
 *
 * Triggers 'finished' callback for batch 5.
 */
function _batch_test_finished_5($success, $results, $operations) {
  _batch_test_finished_helper(5, $success, $results, $operations);
}
