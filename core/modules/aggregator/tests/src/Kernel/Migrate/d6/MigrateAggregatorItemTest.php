<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\aggregator\Entity\Item;

/**
 * Tests migration of aggregator items.
 *
 * @group aggregator
 */
class MigrateAggregatorItemTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
    $this->executeMigrations(['d6_aggregator_feed', 'd6_aggregator_item']);
  }

  /**
   * Tests Drupal 6 aggregator item migration to Drupal 8.
   */
  public function testAggregatorItem() {
    /** @var \Drupal\aggregator\Entity\Item $item */
    $item = Item::load(1);
    $this->assertSame('1', $item->id());
    $this->assertSame('5', $item->getFeedId());
    $this->assertSame('This (three) weeks in Drupal Core - January 10th 2014', $item->label());
    $this->assertSame('larowlan', $item->getAuthor());
    $this->assertSame("<h2 id='new'>What's new with Drupal 8?</h2>", $item->getDescription());
    $this->assertSame('https://groups.drupal.org/node/395218', $item->getLink());
    $this->assertSame('1389297196', $item->getPostedTime());
    $this->assertSame('en', $item->language()->getId());
    $this->assertSame('395218 at https://groups.drupal.org', $item->getGuid());

  }

}
