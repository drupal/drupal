<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Update feeds on cron.
 *
 * @group aggregator
 */
class AggregatorCronTest extends AggregatorTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Adds feeds and updates them via cron process.
   */
  public function testCron() {
    // Create feed and test basic updating on cron.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $count_query = \Drupal::entityQuery('aggregator_item')->condition('fid', $feed->id())->count();

    $this->cronRun();
    $this->assertEqual(5, $count_query->execute());
    $this->deleteFeedItems($feed);
    $this->assertEqual(0, $count_query->execute());
    $this->cronRun();
    $this->assertEqual(5, $count_query->execute());

    // Test feed locking when queued for update.
    $this->deleteFeedItems($feed);
    $feed->setQueuedTime(REQUEST_TIME)->save();
    $this->cronRun();
    $this->assertEqual(0, $count_query->execute());
    $feed->setQueuedTime(0)->save();
    $this->cronRun();
    $this->assertEqual(5, $count_query->execute());
  }

}
