<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\RemoveFeedTest.
 */

namespace Drupal\aggregator\Tests;

class RemoveFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Remove feed functionality',
      'description' => 'Remove feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Remove a feed and ensure that all it services are removed.
   */
  function testRemoveFeed() {
    $feed = $this->createFeed();

    // Delete feed.
    $this->deleteFeed($feed);

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(404, t('Deleted feed source does not exists.'));

    // Check database for feed.
    $result = db_query("SELECT COUNT(*) FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed->title, ':url' => $feed->url))->fetchField();
    $this->assertFalse($result, t('Feed not found in database'));
  }
}
