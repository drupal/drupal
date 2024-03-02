<?php

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A queue worker for testing cron exception handling.
 */
#[QueueWorker(
  id: 'cron_queue_test_database_delay_exception',
  title: new TranslatableMarkup('Database delay exception test'),
  cron: ['time' => 1]
)]
class CronQueueTestDatabaseDelayException extends QueueWorkerBase {

  const DELAY_INTERVAL = 100;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    throw new DelayedRequeueException(self::DELAY_INTERVAL);
  }

}
