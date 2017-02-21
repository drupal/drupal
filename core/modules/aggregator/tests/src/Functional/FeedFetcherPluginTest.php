<?php

namespace Drupal\Tests\aggregator\Functional;

/**
 * Tests the fetcher plugins functionality and discoverability.
 *
 * @group aggregator
 *
 * @see \Drupal\aggregator_test\Plugin\aggregator\fetcher\TestFetcher.
 */
class FeedFetcherPluginTest extends AggregatorTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Enable test plugins.
    $this->enableTestPlugins();
    // Create some nodes.
    $this->createSampleNodes();
  }

  /**
   * Test fetching functionality.
   */
  public function testfetch() {
    // Create feed with local url.
    $feed = $this->createFeed();
    $this->updateFeedItems($feed);
    $this->assertFalse(empty($feed->items));

    // Delete items and restore checked property to 0.
    $this->deleteFeedItems($feed);
    // Change its name and try again.
    $feed->setTitle('Do not fetch');
    $feed->save();
    $this->updateFeedItems($feed);
    // Fetch should fail due to feed name.
    $this->assertTrue(empty($feed->items));
  }

}
