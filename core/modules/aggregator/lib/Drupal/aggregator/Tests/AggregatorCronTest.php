<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorCronTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests functionality of the cron process in the Aggregator module.
 */
class AggregatorCronTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Update on cron functionality',
      'description' => 'Update feeds on cron.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Adds feeds and updates them via cron process.
   */
  public function testCron() {
    // Create feed and test basic updating on cron.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField(), 'Expected number of items in database.');
    $this->removeFeedItems($feed);
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField(), 'Expected number of items in database.');
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField(), 'Expected number of items in database.');

    // Test feed locking when queued for update.
    $this->removeFeedItems($feed);
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields(array(
        'queued' => REQUEST_TIME,
      ))
      ->execute();
    $this->cronRun();
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField(), 'Expected number of items in database.');
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields(array(
        'queued' => 0,
      ))
      ->execute();
    $this->cronRun();
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField(), 'Expected number of items in database.');
  }
}
