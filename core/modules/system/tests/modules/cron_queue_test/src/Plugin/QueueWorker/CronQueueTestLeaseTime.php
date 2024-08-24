<?php

declare(strict_types=1);

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A queue worker for testing lease time.
 */
#[QueueWorker(
  id: 'cron_queue_test_lease_time',
  title: new TranslatableMarkup('Lease time test'),
  cron: ['time' => 100]
)]
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
