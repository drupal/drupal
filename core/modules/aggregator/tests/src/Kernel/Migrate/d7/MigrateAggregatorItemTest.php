<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d7;

use Drupal\aggregator\Entity\Item;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of aggregator items.
 *
 * @group migrate_drupal_7
 */
class MigrateAggregatorItemTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      $this->assertSame($original->fid, $item->getFeedId());
      $this->assertSame($original->title, $item->label());
      // If $original->author is an empty string, getAuthor() returns NULL so
      // we need to use assertEqual() here.
      $this->assertEquals($original->author, $item->getAuthor());
      $this->assertSame($original->description, $item->getDescription());
      $this->assertSame($original->link, $item->getLink());
      $this->assertSame($original->timestamp, $item->getPostedTime());
      $this->assertSame('en', $item->language()->getId());
      $this->assertSame($original->guid, $item->getGuid());
    }
  }

}
