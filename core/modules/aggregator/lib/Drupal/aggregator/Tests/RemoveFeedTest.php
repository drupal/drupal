<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\RemoveFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests functionality for removing feeds in the Aggregator module.
 */
class RemoveFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Remove feed functionality',
      'description' => 'Remove feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Removes a feed and ensures that all of its services are removed.
   */
  function testRemoveFeed() {
    $feed = $this->createFeed();

    // Delete feed.
    $this->deleteFeed($feed);

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(404, 'Deleted feed source does not exists.');

    // Check database for feed.
    $result = db_query("SELECT COUNT(*) FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed->label(), ':url' => $feed->url->value))->fetchField();
    $this->assertFalse($result, 'Feed not found in database');
  }
}
