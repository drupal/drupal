<?php

declare(strict_types=1);

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cron_queue_test\Plugin\Derivative\CronQueueTestDeriver;

/**
 * A queue worker for testing derivatives.
 */
#[QueueWorker(
  id: self::PLUGIN_ID,
  title: new TranslatableMarkup('Deriver queue test'),
  cron: ['time' => 1],
  deriver: CronQueueTestDeriver::class
)]
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
