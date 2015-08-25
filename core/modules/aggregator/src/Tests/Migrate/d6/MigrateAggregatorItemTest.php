<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Migrate\d6\MigrateAggregatorItemTest.
 */

namespace Drupal\aggregator\Tests\Migrate\d6;

use Drupal\aggregator\Entity\Item;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade aggregator items.
 *
 * @group migrate_drupal_6
 */
class MigrateAggregatorItemTest extends MigrateDrupal6TestBase {

  static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_aggregator_feed' => array(
        array(array(5), array(5)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $entity = entity_create('aggregator_feed', array(
      'fid' => 5,
      'title' => 'Drupal Core',
      'url' => 'https://groups.drupal.org/not_used/167169',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal Core Group feed',
    ));
    $entity->enforceIsNew();
    $entity->save();
    $this->executeMigration('d6_aggregator_item');
  }

  /**
   * Test Drupal 6 aggregator item migration to Drupal 8.
   */
  public function testAggregatorItem() {
    /** @var Item $item */
    $item = Item::load(1);
    $this->assertIdentical('1', $item->id());
    $this->assertIdentical('5', $item->getFeedId());
    $this->assertIdentical('This (three) weeks in Drupal Core - January 10th 2014', $item->label());
    $this->assertIdentical('larowlan', $item->getAuthor());
    $this->assertIdentical("<h2 id='new'>What's new with Drupal 8?</h2>", $item->getDescription());
    $this->assertIdentical('https://groups.drupal.org/node/395218', $item->getLink());
    $this->assertIdentical('1389297196', $item->getPostedTime());
    $this->assertIdentical('en', $item->language()->getId());
    $this->assertIdentical('395218 at https://groups.drupal.org', $item->getGuid());

  }

}
