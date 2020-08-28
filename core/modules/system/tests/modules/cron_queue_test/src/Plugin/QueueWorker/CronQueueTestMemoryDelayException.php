<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * A queue worker for testing cron exception handling.
 *
 * @QueueWorker(
 *   id = "cron_queue_test_memory_delay_exception",
 *   title = @Translation("Memory delay exception test"),
 *   cron = {"time" = 1}
 * )
 */
class CronQueueTestMemoryDelayException extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Set the delay to something larger than the original lease.
    $cron_time = $this->pluginDefinition['cron']['time'];
    throw new DelayedRequeueException($cron_time + 100);
  }

}
