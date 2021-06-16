<?php

namespace Drupal\aggregator\Plugin\QueueWorker;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "aggregator_feeds",
 *   title = @Translation("Aggregator refresh"),
 *   cron = {"time" = 60}
 * )
 */
class AggregatorRefresh extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof FeedInterface) {
      $data->refreshItems();
    }
  }

}
