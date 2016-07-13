<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "cron_queue_test_exception",
 *   title = @Translation("Exception test"),
 *   cron = {"time" = 1}
 * )
 */
class CronQueueTestException extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $state = \Drupal::state();
    if (!$state->get('cron_queue_test_exception')) {
      $state->set('cron_queue_test_exception', 1);
      throw new \Exception('That is not supposed to happen.');
    }
    else {
      $state->set('cron_queue_test_exception', 2);
    }
  }

}
