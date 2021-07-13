<?php

namespace Drupal\Tests\aggregator\Kernel\Views;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\Tests\views\Kernel\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the aggregator_item entity.
 *
 * @group aggregator
 */
class AggregatorItemViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator', 'entity_test', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installConfig(['aggregator']);
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
  }

  /**
   * Checks access for aggregator_item fields.
   */
  public function testAggregatorItemFields() {
    $feed = Feed::create([
      'title' => 'Drupal org',
      'url' => 'https://www.drupal.org/rss.xml',
    ]);
    $feed->save();
    $item = Item::create([
      'title' => 'Test title',
      'fid' => $feed->id(),
      'description' => 'Test description',
    ]);

    $item->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    $this->assertFieldAccess('aggregator_item', 'title', $item->getTitle());
    $this->assertFieldAccess('aggregator_item', 'langcode', $item->language()->getName());
    $this->assertFieldAccess('aggregator_item', 'description', $item->getDescription());
  }

}
