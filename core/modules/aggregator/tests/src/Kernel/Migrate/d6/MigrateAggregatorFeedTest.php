<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\aggregator\Entity\Feed;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of aggregator feeds.
 *
 * @group migrate_drupal_6
 */
class MigrateAggregatorFeedTest extends MigrateDrupal6TestBase {

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
    $this->executeMigration('d6_aggregator_feed');
  }

  /**
   * Tests migration of aggregator feeds.
   */
  public function testAggregatorFeedImport() {
    /** @var \Drupal\aggregator\Entity\Feed $feed */
    $feed = Feed::load(5);
    $this->assertIdentical('Know Your Meme', $feed->title->value);
    $this->assertIdentical('en', $feed->language()->getId());
    $this->assertIdentical('http://knowyourmeme.com/newsfeed.rss', $feed->url->value);
    $this->assertIdentical('900', $feed->refresh->value);
    $this->assertIdentical('1387659487', $feed->checked->value);
    $this->assertIdentical('0', $feed->queued->value);
    $this->assertIdentical('http://knowyourmeme.com', $feed->link->value);
    $this->assertIdentical('New items added to the News Feed', $feed->description->value);
    $this->assertIdentical('http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png', $feed->image->value);
    $this->assertIdentical('"213cc1365b96c310e92053c5551f0504"', $feed->etag->value);
    $this->assertIdentical('0', $feed->modified->value);
  }
}
