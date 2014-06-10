<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateAggregatorItemTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\aggregator\Entity\Item;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Test Drupal 6 aggregator item migration to Drupal 8.
 */
class MigrateAggregatorItemTest extends MigrateDrupalTestBase {

  static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate aggregator items',
      'description'  => 'Upgrade aggregator items',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_aggregator_feed' => array(
        array(array(5), array(5)),
      ),
    );
    $this->prepareIdMappings($id_mappings);

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
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_aggregator_item');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6AggregatorItem.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test Drupal 6 aggregator item migration to Drupal 8.
   */
  public function testAggregatorItem() {
    /** @var Item $item */
    $item = entity_load('aggregator_item', 1);
    $this->assertEqual($item->id(), 1);
    $this->assertEqual($item->getFeedId(), 5);
    $this->assertEqual($item->label(), 'This (three) weeks in Drupal Core - January 10th 2014');
    $this->assertEqual($item->getAuthor(), 'larowlan');
    $this->assertEqual($item->getDescription(), "<h2 id='new'>What's new with Drupal 8?</h2>");
    $this->assertEqual($item->getLink(), 'https://groups.drupal.org/node/395218');
    $this->assertEqual($item->getPostedTime(), 1389297196);
    $this->assertEqual($item->language()->id, LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->assertEqual($item->getGuid(), '395218 at https://groups.drupal.org');

  }

}
