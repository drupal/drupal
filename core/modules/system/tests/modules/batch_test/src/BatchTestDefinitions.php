<?php

declare(strict_types=1);

namespace Drupal\batch_test;

use Drupal\Core\Batch\BatchBuilder;

/**
 * Batch definitions for testing batches.
 */
class BatchTestDefinitions {

  /**
   * Batch 0: Does nothing.
   */
  public function batch0(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished0']);
    return $batch_builder->toArray() + ['batch_test_id' => 'batch0'];
  }

  /**
   * Batch 1: Repeats a simple operation.
   *
   * Operations: op 1 from 1 to 10.
   */
  public function batch1(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (int) (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished1']);

    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback1'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch1'];
  }

  /**
   * Batch 2: Performs a single multistep operation.
   *
   * Operations: op 2 from 1 to 10.
   */
  public function batch2(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->addOperation([$batch_test_callbacks, 'callback2'], [1, $total, $sleep])
      ->setFinishCallback([$batch_test_callbacks, 'finished2']);

    return $batch_builder->toArray() + ['batch_test_id' => 'batch2'];
  }

  /**
   * Batch 3: Performs both single and multistep operations.
   *
   * Operations:
   * - op 1 from 1 to 5,
   * - op 2 from 1 to 5,
   * - op 1 from 6 to 10,
   * - op 2 from 6 to 10.
   */
  public function batch3(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished3']);
    for ($i = 1; $i <= round($total / 2); $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'callback2'], [1, $total / 2, $sleep]);
    for ($i = round($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'callback2'], [6, $total / 2, $sleep]);

    return $batch_builder->toArray() + ['batch_test_id' => 'batch3'];
  }

  /**
   * Batch 4: Performs a batch within a batch.
   *
   * Operations:
   * - op 1 from 1 to 5,
   * - set batch 2 (op 2 from 1 to 10, should run at the end)
   * - op 1 from 6 to 10,
   */
  public function batch4(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished4']);
    for ($i = 1; $i <= round($total / 2); $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback1'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'nestedBatchCallback'], [[2]]);
    for ($i = round($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback1'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch4'];
  }

  /**
   * Batch 5: Repeats a simple operation.
   *
   * Operations: op 1 from 1 to 10.
   */
  public function batch5(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished5']);
    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback5'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch5'];
  }

  /**
   * Batch 6: Repeats a simple operation.
   *
   * Operations: op 6 from 1 to 10.
   */
  public function batch6(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished6']);
    for ($i = 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback6'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch6'];
  }

  /**
   * Batch 7: Performs two batches within a batch.
   *
   * Operations:
   * - op 7 from 1 to 5,
   * - set batch 5 (op 5 from 1 to 10, should run at the end before batch 2)
   * - set batch 6 (op 6 from 1 to 10, should run at the end after batch 1)
   * - op 7 from 6 to 10,
   */
  public function batch7(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    // Ensure the batch takes at least two iterations.
    $total = 10;
    $sleep = (1000000 / $total) * 2;

    $batch_builder = (new BatchBuilder())
      ->setFinishCallback([$batch_test_callbacks, 'finished7']);
    for ($i = 1; $i <= $total / 2; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback7'], [$i, $sleep]);
    }
    $batch_builder->addOperation([$batch_test_callbacks, 'nestedBatchCallback'], [[6, 5]]);
    for ($i = ($total / 2) + 1; $i <= $total; $i++) {
      $batch_builder->addOperation([$batch_test_callbacks, 'callback7'], [$i, $sleep]);
    }

    return $batch_builder->toArray() + ['batch_test_id' => 'batch7'];
  }

  /**
   * Batch 8: Throws an exception.
   */
  public function batch8(): array {
    $batch_test_callbacks = new BatchTestCallbacks();
    $batch_builder = (new BatchBuilder())
      ->addOperation([$batch_test_callbacks, 'callback8'], [FALSE])
      ->addOperation([$batch_test_callbacks, 'callback8'], [TRUE]);
    return $batch_builder->toArray() + ['batch_test_id' => 'batch8'];
  }

}
