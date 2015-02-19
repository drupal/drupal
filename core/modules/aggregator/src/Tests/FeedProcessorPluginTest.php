<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedProcessorPluginTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;

/**
 * Tests the processor plugins functionality and discoverability.
 *
 * @group aggregator
 *
 * @see \Drupal\aggregator_test\Plugin\aggregator\processor\TestProcessor.
 */
class FeedProcessorPluginTest extends AggregatorTestBase {

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
   * Test processing functionality.
   */
  public function testProcess() {
    $feed = $this->createFeed();
    $this->updateFeedItems($feed);
    foreach ($feed->items as $iid) {
      $item = Item::load($iid);
      $this->assertTrue(strpos($item->label(), 'testProcessor') === 0);
    }
  }

  /**
   * Test deleting functionality.
   */
  public function testDelete() {
    $feed = $this->createFeed();
    $this->updateAndDelete($feed, NULL);
    // Make sure the feed title is changed.
    $entities = entity_load_multiple_by_properties('aggregator_feed', array('description' => $feed->description->value));
    $this->assertTrue(empty($entities));
  }

  /**
   * Test post-processing functionality.
   */
  public function testPostProcess() {
    $feed = $this->createFeed(NULL, array('refresh' => 1800));
    $this->updateFeedItems($feed);
    $feed_id = $feed->id();
    // Reset entity cache manually.
    \Drupal::entityManager()->getStorage('aggregator_feed')->resetCache(array($feed_id));
    // Reload the feed to get new values.
    $feed = Feed::load($feed_id);
    // Make sure its refresh rate doubled.
    $this->assertEqual($feed->getRefreshRate(), 3600);
  }
}
