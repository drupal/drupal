<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateAggregatorFeedTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\aggregator\Entity\Feed;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to aggregator_feed entities.
 *
 * @group migrate_drupal
 */
class MigrateAggregatorFeedTest extends MigrateDrupalTestBase {

  static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_aggregator_feed');
    $dumps = array(
      $this->getDumpDirectory() . '/AggregatorFeed.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of aggregator feeds.
   */
  public function testAggregatorFeedImport() {
    /** @var Feed $feed */
    $feed = Feed::load(5);
    $this->assertNotNull($feed->uuid());
    $this->assertEqual($feed->title->value, 'Know Your Meme');
    $this->assertEqual($feed->language()->getId(), 'en');
    $this->assertEqual($feed->url->value, 'http://knowyourmeme.com/newsfeed.rss');
    $this->assertEqual($feed->refresh->value, 900);
    $this->assertEqual($feed->checked->value, 1387659487);
    $this->assertEqual($feed->queued->value, 0);
    $this->assertEqual($feed->link->value, 'http://knowyourmeme.com');
    $this->assertEqual($feed->description->value, 'New items added to the News Feed');
    $this->assertEqual($feed->image->value, 'http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png');
    $this->assertEqual($feed->hash->value, '');
    $this->assertEqual($feed->etag->value, '"213cc1365b96c310e92053c5551f0504"');
    $this->assertEqual($feed->modified->value, 0);
  }
}
