<?php

declare(strict_types=1);

namespace Drupal\cron_queue_test\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A queue worker for testing exceptions.
 */
#[QueueWorker(
  id: self::PLUGIN_ID,
  title: new TranslatableMarkup('Exception test'),
  cron: ['time' => 1]
)]
class CronQueueTestException extends QueueWorkerBase {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'cron_queue_test_exception';

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
