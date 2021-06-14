<?php

namespace Drupal\Tests\aggregator\Kernel\Views;

use Drupal\aggregator\Entity\Feed;
use Drupal\Tests\views\Kernel\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the aggregator_feed entity.
 *
 * @group aggregator
 */
class AggregatorFeedViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator', 'entity_test', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('aggregator_feed');
  }

  /**
   * Checks access for aggregator_feed fields.
   */
  public function testAggregatorFeedFields() {
    $feed = Feed::create([
      'title' => 'Drupal org',
      'url' => 'https://www.drupal.org/rss.xml',
      'link' => 'https://www.drupal.org/rss.xml',
    ]);
    $feed->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    // $this->assertFieldAccess('aggregator_feed', 'title', $feed->label());
    $this->assertFieldAccess('aggregator_feed', 'langcode', $feed->language()->getName());
    $this->assertFieldAccess('aggregator_feed', 'url', $feed->getUrl());
  }

}
