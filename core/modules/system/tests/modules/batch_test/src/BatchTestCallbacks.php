<?php

declare(strict_types=1);

namespace Drupal\batch_test;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Batch callbacks for testing batches.
 */
class BatchTestCallbacks {
  use StringTranslationTrait;

  /**
   * Implements callback_batch_operation().
   *
   * Tests the progress page theme.
   */
  public function themeCallback(): void {
    $batch_test_helper = new BatchTestHelper();
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // theme is being used on the batch processing page by viewing that page
    // directly. Instead, we save the theme being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $batch_test_helper->stack($theme);
  }

  /**
   * Tests the title on the progress page by performing a batch callback.
   */
  public function titleCallback(): void {
    $batch_test_helper = new BatchTestHelper();
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // title is being used on the batch processing page by viewing that page
    // directly. Instead, we save the title being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    $batch_test_helper->stack($title);
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs a simple batch operation.
   */
  public function callback1($id, $sleep, &$context): void {
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
   * Implements callback_batch_operation().
   *
   * Performs a multistep batch operation.
   */
  public function callback2($start, $total, $sleep, &$context): void {
    $batch_test_helper = new BatchTestHelper();
    // Initialize context with progress information.
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['current'] = $start;
      $context['sandbox']['count'] = 0;
    }

    // Process by groups of 5 (arbitrary value).
    $limit = 5;
    for ($i = 0; $i < $limit && $context['sandbox']['count'] < $total; $i++) {
      // No-op, but ensure the batch takes a couple iterations.
      // Batch needs time to run for the test, so sleep a bit.
      usleep($sleep);
      // Track execution, and store some result for post-processing in the
      // 'finished' callback.
      $id = $context['sandbox']['current'] + $i;
      $batch_test_helper->stack("op 2 id $id");
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
  public function callback5($id, $sleep, &$context): void {
    $batch_test_helper = new BatchTestHelper();
    // No-op, but ensure the batch takes a couple iterations.
    // Batch needs time to run for the test, so sleep a bit.
    usleep($sleep);
    // Track execution, and store some result for post-processing in the
    // 'finished' callback.
    $batch_test_helper->stack("op 5 id $id");
    $context['results'][5][] = $id;
    // This test is to test finished > 1
    $context['finished'] = 3.14;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs a simple batch operation.
   */
  public function callback6($id, $sleep, &$context): void {
    $batch_test_helper = new BatchTestHelper();
    // No-op, but ensure the batch takes a couple iterations.
    // Batch needs time to run for the test, so sleep a bit.
    usleep($sleep);
    // Track execution, and store some result for post-processing in the
    // 'finished' callback.
    $batch_test_helper->stack("op 6 id $id");
    $context['results'][6][] = $id;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs a simple batch operation.
   */
  public function callback7($id, $sleep, &$context): void {
    $batch_test_helper = new BatchTestHelper();
    // No-op, but ensure the batch takes a couple iterations.
    // Batch needs time to run for the test, so sleep a bit.
    usleep($sleep);
    // Track execution, and store some result for post-processing in the
    // 'finished' callback.
    $batch_test_helper->stack("op 7 id $id");
    $context['results'][7][] = $id;
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs a simple batch operation that optionally throws an exception.
   */
  public function callback8(bool $throw_exception): void {
    usleep(500);
    if ($throw_exception) {
      throw new \Exception('Exception in batch');
    }
  }

  /**
   * Implements callback_batch_operation().
   *
   * Performs a batch operation setting up its own batch(es).
   */
  public function nestedBatchCallback(array $batches = []): void {
    $batch_test_definitions = new BatchTestDefinitions();
    $batch_test_helper = new BatchTestHelper();
    foreach ($batches as $batch) {
      $batch_test_helper->stack("setting up batch $batch");
      $function = 'batch' . $batch;
      batch_set($batch_test_definitions->$function());
    }
    \Drupal::state()
      ->set('batch_test_nested_order_multiple_batches', batch_get());
  }

  /**
   * Provides a common 'finished' callback for batches 1 to 7.
   */
  public function finishedHelper($batch_id, $success, $results, $operations, $elapsed): void {
    $messages = [];
    if ($results) {
      foreach ($results as $op => $op_results) {
        $messages[] = 'op ' . Html::escape((string) $op) . ': processed ' . count($op_results) . ' elements';
      }
    }
    else {
      $messages[] = 'none';
    }

    if (!$success) {
      // A fatal error occurred during the processing.
      $error_operation = reset($operations);
      $messages[] = $this->t('An error occurred while processing @op with arguments:<br />@args', [
        '@op' => $error_operation[0],
        '@args' => print_r($error_operation[1], TRUE),
      ]);
    }

    // Use item list template to render the messages.
    $error_message = [
      '#type' => 'inline_template',
      '#template' => 'results for batch {{ batch_id }} ({{ elapsed }}){{ errors }}',
      '#context' => [
        'batch_id' => $batch_id,
        'elapsed' => $elapsed,
        'errors' => [
          '#theme' => 'item_list',
          '#items' => $messages,
        ],
      ],
    ];

    \Drupal::messenger()->addStatus(\Drupal::service('renderer')->renderInIsolation($error_message));

    \Drupal::messenger()->addMessage('elapsed time: ' . $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 0.
   */
  public function finished0($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(0, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 1.
   */
  public function finished1($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(1, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 1.
   */
  public function finished1Finished($success, $results, $operations, $elapsed): RedirectResponse {
    $this->finishedHelper(1, $success, $results, $operations, $elapsed);
    return new RedirectResponse(Url::fromRoute('test_page_test.test_page', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 2.
   */
  public function finished2($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(2, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 3.
   */
  public function finished3($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(3, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 4.
   */
  public function finished4($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(4, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 5.
   */
  public function finished5($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(5, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 6.
   */
  public function finished6($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(6, $success, $results, $operations, $elapsed);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Triggers 'finished' callback for batch 7.
   */
  public function finished7($success, $results, $operations, $elapsed): void {
    $this->finishedHelper(7, $success, $results, $operations, $elapsed);
  }

}
