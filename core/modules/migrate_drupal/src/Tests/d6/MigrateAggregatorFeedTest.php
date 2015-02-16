<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateAggregatorFeedTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\aggregator\Entity\Feed;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to aggregator_feed entities.
 *
 * @group migrate_drupal
 */
class MigrateAggregatorFeedTest extends MigrateDrupal6TestBase {

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
    $this->assertIdentical($feed->title->value, 'Know Your Meme');
    $this->assertIdentical($feed->language()->getId(), 'en');
    $this->assertIdentical($feed->url->value, 'http://knowyourmeme.com/newsfeed.rss');
    $this->assertIdentical($feed->refresh->value, '900');
    $this->assertIdentical($feed->checked->value, '1387659487');
    $this->assertIdentical($feed->queued->value, '0');
    $this->assertIdentical($feed->link->value, 'http://knowyourmeme.com');
    $this->assertIdentical($feed->description->value, 'New items added to the News Feed');
    $this->assertIdentical($feed->image->value, 'http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png');
    $this->assertIdentical($feed->etag->value, '"213cc1365b96c310e92053c5551f0504"');
    $this->assertIdentical($feed->modified->value, '0');
  }
}
