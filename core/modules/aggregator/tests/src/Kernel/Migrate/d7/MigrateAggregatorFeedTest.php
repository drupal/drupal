<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d7;

use Drupal\aggregator\Entity\Feed;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test migration to aggregator_feed entities.
 *
 * @group migrate_drupal_7
 */
class MigrateAggregatorFeedTest extends MigrateDrupal7TestBase {

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
    $this->executeMigration('d7_aggregator_feed');
  }

  /**
   * Tests migration of aggregator feeds.
   */
  public function testAggregatorFeedImport() {
    /** @var \Drupal\aggregator\FeedInterface $feed */
    $feed = Feed::load(1);
    $this->assertIdentical('Know Your Meme', $feed->label());
    $this->assertIdentical('en', $feed->language()->getId());
    $this->assertIdentical('http://knowyourmeme.com/newsfeed.rss', $feed->getUrl());
    $this->assertIdentical('900', $feed->getRefreshRate());
    // The feed's last checked time can change as the fixture is updated, so
    // assert that its format is correct.
    $checked_time = $feed->getLastCheckedTime();
    $this->assertTrue(is_numeric($checked_time));
    $this->assertTrue($checked_time > 1000000000);
    $this->assertIdentical('0', $feed->getQueuedTime());
    $this->assertIdentical('http://knowyourmeme.com', $feed->link->value);
    $this->assertIdentical('New items added to the News Feed', $feed->getDescription());
    $this->assertNull($feed->getImage());
    // As with getLastCheckedTime(), the etag can change as the fixture is
    // updated normally, so assert that its format is correct.
    $this->assertRegExp('/^"[a-z0-9]{32}"$/', $feed->getEtag());
    $this->assertIdentical('0', $feed->getLastModified());
  }

}
