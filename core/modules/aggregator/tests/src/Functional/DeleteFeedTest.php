<?php

namespace Drupal\Tests\aggregator\Functional;

/**
 * Delete feed test.
 *
 * @group aggregator
 */
class DeleteFeedTest extends AggregatorTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Deletes a feed and ensures that all of its services are deleted.
   */
  public function testDeleteFeed() {
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
    $this->assertSession()->pageTextContains($feed2->label());
    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');
    $this->assertNull($block_storage->load($block->id()), 'Block for the deleted feed was deleted.');
    $this->assertEquals($block2->id(), $block_storage->load($block2->id())->id(), 'Block for not deleted feed still exists.');

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed1->id());
    $this->assertSession()->statusCodeEquals(404);

    // Check database for feed.
    $result = \Drupal::entityQuery('aggregator_feed')
      ->accessCheck(FALSE)
      ->condition('title', $feed1->label())
      ->condition('url', $feed1->getUrl())
      ->count()
      ->execute();
    $this->assertEquals(0, $result, 'Feed not found in database');
  }

}
