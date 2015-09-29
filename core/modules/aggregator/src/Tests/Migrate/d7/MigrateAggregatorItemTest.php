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
 * @group aggregator
 */
class MigrateAggregatorItemTest extends MigrateDrupal7TestBase {

  public static $modules = array('aggregator');

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
    /** @var \Drupal\aggregator\Entity\Item $item */
    $item = Item::load(1);
    $this->assertIdentical('1', $item->id());
    $this->assertIdentical('1', $item->getFeedId());
    $this->assertIdentical('This (three) weeks in Drupal Core - January 10th 2014', $item->label());
    $this->assertIdentical('larowlan', $item->getAuthor());
    $this->assertIdentical("<h2 id='new'>What's new with Drupal 8?</h2>", $item->getDescription());
    $this->assertIdentical('https://groups.drupal.org/node/395218', $item->getLink());
    $this->assertIdentical('1389297196', $item->getPostedTime());
    $this->assertIdentical('en', $item->language()->getId());
    $this->assertIdentical('395218 at https://groups.drupal.org', $item->getGuid());
  }

}
