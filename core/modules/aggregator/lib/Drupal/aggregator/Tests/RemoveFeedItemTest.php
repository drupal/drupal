<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\RemoveFeedItemTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests functionality of removing a feed item in the Aggregator module.
 */
class RemoveFeedItemTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Remove feed item functionality',
      'description' => 'Remove feed items from a feed.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Tests running "remove items" from 'admin/config/services/aggregator' page.
   */
  function testRemoveFeedItem() {
    // Create a bunch of test feeds.
    $feed_urls = array();
    // No last-modified, no etag.
    $feed_urls[] = url('aggregator/test-feed', array('absolute' => TRUE));
    // Last-modified, but no etag.
    $feed_urls[] = url('aggregator/test-feed/1', array('absolute' => TRUE));
    // No Last-modified, but etag.
    $feed_urls[] = url('aggregator/test-feed/0/1', array('absolute' => TRUE));
    // Last-modified and etag.
    $feed_urls[] = url('aggregator/test-feed/1/1', array('absolute' => TRUE));

    foreach ($feed_urls as $feed_url) {
      $feed = $this->createFeed($feed_url);
      // Update and remove items two times in a row to make sure that removal
      // resets all 'modified' information (modified, etag, hash) and allows for
      // immediate update. There's 8 items in the feed, but one has an empty
      // title and is skipped.
      $this->updateAndRemove($feed, 7);
      $this->updateAndRemove($feed, 7);
      $this->updateAndRemove($feed, 7);
      // Delete feed.
      $this->deleteFeed($feed);
    }
  }
}
