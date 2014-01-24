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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

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
    $feed1 = $this->createFeed();
    $feed2 = $this->createFeed();

    // Place a block for both feeds.
    $block = $this->drupalPlaceBlock('aggregator_feed_block');
    $block->getPlugin()->setConfigurationValue('feed', $feed1->id());
    $block->save();
    $block2 = $this->drupalPlaceBlock('aggregator_feed_block');
    $block2->getPlugin()->setConfigurationValue('feed', $feed2->id());
    $block2->save();

    // Delete feed.
    $this->deleteFeed($feed1);
    $this->assertText($feed2->label());
    $block_storage = $this->container->get('entity.manager')->getStorageController('block');
    $this->assertNull($block_storage->load($block->id()), 'Block for the deleted feed was deleted.');
    $this->assertEqual($block2->id(), $block_storage->load($block2->id())->id(), 'Block for not deleted feed still exists.');

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed1->id());
    $this->assertResponse(404, 'Deleted feed source does not exists.');

    // Check database for feed.
    $result = db_query("SELECT COUNT(*) FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed1->label(), ':url' => $feed1->getUrl()))->fetchField();
    $this->assertFalse($result, 'Feed not found in database');
  }

}
