<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A queue worker for testing suspending queue run.
 */
#[QueueWorker(
  id: self::PLUGIN_ID,
  title: new TranslatableMarkup('Suspend queue test'),
  cron: ['time' => 60]
)]
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
