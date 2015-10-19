<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Migrate\d7\MigrateAggregatorItemTest.
 */

namespace Drupal\aggregator\Tests\Migrate\d7;

use Drupal\aggregator\Entity\Item;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of aggregator items.
 *
 * @group migrate_drupal_7
 */
class MigrateAggregatorItemTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
    $this->executeMigration('d7_aggregator_feed');
    $this->executeMigration('d7_aggregator_item');
  }

  /**
   * Test Drupal 7 aggregator item migration to Drupal 8.
   */
  public function testAggregatorItem() {
    // Since the feed items can change as the fixture is updated normally,
    // assert all migrated feed items against the values in the fixture.
    $items = $this->sourceDatabase
      ->select('aggregator_item', 'ai')
      ->fields('ai')
      ->execute();

    foreach ($items as $original) {
      /** @var \Drupal\aggregator\ItemInterface $item */
      $item = Item::load($original->iid);
      $this->assertIdentical($original->fid, $item->getFeedId());
      $this->assertIdentical($original->title, $item->label());
      // If $original->author is an empty string, getAuthor() returns NULL so
      // we need to use assertEqual() here.
      $this->assertEqual($original->author, $item->getAuthor());
      $this->assertIdentical($original->description, $item->getDescription());
      $this->assertIdentical($original->link, $item->getLink());
      $this->assertIdentical($original->timestamp, $item->getPostedTime());
      $this->assertIdentical('en', $item->language()->getId());
      $this->assertIdentical($original->guid, $item->getGuid());
    }
  }

}
