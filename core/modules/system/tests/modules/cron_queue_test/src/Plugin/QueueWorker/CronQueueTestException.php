<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "cron_queue_test_exception",
 *   title = @Translation("Exception test"),
 *   cron = {"time" = 60}
 * )
 */
class CronQueueTestException extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    throw new \Exception('That is not supposed to happen.');
  }

}
