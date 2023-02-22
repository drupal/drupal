<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * @QueueWorker(
 *   id = \Drupal\cron_queue_test\Plugin\QueueWorker\CronQueueTestSuspendQueue::PLUGIN_ID,
 *   title = @Translation("Suspend queue test"),
 *   cron = {"time" = 60}
 * )
 */
class CronQueueTestSuspendQueue extends QueueWorkerBase {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'cron_queue_test_suspend';

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data === 'suspend') {
      throw new SuspendQueueException('The queue is broken.');
    }
    // Do nothing otherwise.
  }

}
