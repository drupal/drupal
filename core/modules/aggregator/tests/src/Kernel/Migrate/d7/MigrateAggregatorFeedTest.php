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
  protected static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    $this->assertSame('Know Your Meme', $feed->label());
    $this->assertSame('en', $feed->language()->getId());
    $this->assertSame('http://knowyourmeme.com/newsfeed.rss', $feed->getUrl());
    $this->assertSame('900', $feed->getRefreshRate());
    // The feed's last checked time can change as the fixture is updated, so
    // assert that its format is correct.
    $checked_time = $feed->getLastCheckedTime();
    $this->assertIsNumeric($checked_time);
    $this->assertGreaterThan(1000000000, $checked_time);
    $this->assertSame('0', $feed->getQueuedTime());
    $this->assertSame('http://knowyourmeme.com', $feed->link->value);
    $this->assertSame('New items added to the News Feed', $feed->getDescription());
    $this->assertNull($feed->getImage());
    // As with getLastCheckedTime(), the etag can change as the fixture is
    // updated normally, so assert that its format is correct.
    $this->assertMatchesRegularExpression('/^"[a-z0-9]{32}"$/', $feed->getEtag());
    $this->assertSame('0', $feed->getLastModified());
  }

}
