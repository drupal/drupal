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
   * Adds feeds and updates them via cron process.
   */
  public function testCron() {
    // Create feed and test basic updating on cron.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField());
    $this->deleteFeedItems($feed);
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField());
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField());

    // Test feed locking when queued for update.
    $this->deleteFeedItems($feed);
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields([
        'queued' => REQUEST_TIME,
      ])
      ->execute();
    $this->cronRun();
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField());
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields([
        'queued' => 0,
      ])
      ->execute();
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField());
  }

}
