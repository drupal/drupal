<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = \Drupal\cron_queue_test\Plugin\QueueWorker\CronQueueTestDeriverQueue::PLUGIN_ID,
 *   title = @Translation("Deriver queue test"),
 *   cron = {"time" = 1},
 *   deriver = \Drupal\cron_queue_test\Plugin\Derivative\CronQueueTestDeriver::class
 * )
 */
class CronQueueTestDeriverQueue extends QueueWorkerBase {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'cron_queue_test_deriver';

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $state = \Drupal::state();
    $processed = $state->get(self::PLUGIN_ID, 0);
    $state->set(self::PLUGIN_ID, ++$processed);
  }

}
