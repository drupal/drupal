<?php

/**
 * @file
 * Contains \Drupal\cron_queue_test\Plugin\QueueWorker\CronQueueTestBrokenQueue.
 */

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * @QueueWorker(
 *   id = "cron_queue_test_broken_queue",
 *   title = @Translation("Broken queue test"),
 *   cron = {"time" = 60}
 * )
 */
class CronQueueTestBrokenQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data == 'crash') {
      throw new SuspendQueueException('The queue is broken.');
    }
    // Do nothing otherwise.
  }

}
