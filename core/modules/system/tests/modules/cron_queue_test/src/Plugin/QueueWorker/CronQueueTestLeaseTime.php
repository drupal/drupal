<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "cron_queue_test_lease_time",
 *   title = @Translation("Lease time test"),
 *   cron = {"time" = 100}
 * )
 */
class CronQueueTestLeaseTime extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $state = \Drupal::state();
    $count = $state->get('cron_queue_test_lease_time', 0);
    $count++;
    $state->set('cron_queue_test_lease_time', $count);
    throw new \Exception('Leave me queued and leased!');
  }

}
