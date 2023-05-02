<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\Node;

/**
 * Tests aggregator_feed and aggregator_item base fields' displays.
 *
 * @group aggregator
 * @group legacy
 */
class AggregatorDisplayConfigurableTest extends AggregatorTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests base fields to configurable display settings.
   */
  public function testFeedDisplayConfigurable() {
    $display = EntityViewDisplay::load('aggregator_feed.aggregator_feed.summary');
    $display->setComponent('description', ['region' => 'content'])
      ->setComponent('items', ['region' => 'hidden'])
      ->save();

    $feed = $this->createFeed($this->getRSS091Sample());
    $feed->refreshItems();
    $assert = $this->assertSession();

    // Check the aggregator_feed with Drupal default non-configurable display.
    $this->drupalGet('/aggregator/sources');
    $assert->elementTextContains('css', 'div.aggregator-feed > h2', $feed->label());
    $assert->elementTextContains('css', 'div.feed-description', $feed->getDescription());
    $assert->elementNotExists('css', '.field--name-title');
    $assert->elementNotExists('css', '.field--name-description');

    // Enable helper module to make base fields' displays configurable.
    \Drupal::service('module_installer')->install(['aggregator_display_configurable_test']);

    // Configure display.
    $display->setComponent('title', [
      'type' => 'text_default',
      'label' => 'above',
    ]);
    $display->setComponent('description', [
      'type' => 'aggregator_xss',
      'label' => 'hidden',
    ])->save();

    // Recheck the aggregator_feed with configurable display.
    $this->drupalGet('/aggregator/sources');
    $assert->elementTextContains('css', 'div.aggregator-feed > div.field--name-title > div.field__item', $feed->label());
    $assert->elementExists('css', 'div.field--name-title > div.field__label');
    $assert->elementTextContains('css', 'div.field--name-description.field__item', $feed->getDescription());

    // Remove 'title' field from display.
    $display->removeComponent('title')->save();

    // Recheck the aggregator_feed with 'title' field removed from display.
    $this->drupalGet('/aggregator/sources');
    $assert->elementNotExists('css', 'div.aggregator-feed > div.field--name-title');
  }

  /**
   * Tests item base fields settings.
   */
  public function testItemDisplayConfigurable() {
    $this->createSampleNodes(1);
    $item = Node::load(1);
    $feed = $this->createFeed();
    $this->updateFeedItems($feed);
    $assert = $this->assertSession();

    // Check the aggregator_feed with Drupal default non-configurable display.
    $this->drupalGet('/aggregator');
    $assert->elementTextContains('css', 'h3.feed-item-title', $item->label());
    $assert->elementNotExists('css', '.field--name-title .field__item');
    $assert->elementNotExists('css', '.field--name-title .field__label');

    // Enable helper module to make base fields' displays configurable.
    \Drupal::service('module_installer')->install(['aggregator_display_configurable_test']);

    // Recheck the aggregator_feed with configurable display.
    $this->drupalGet('/aggregator');
    $assert->elementNotExists('css', 'h3.feed-item-title');
    $assert->elementTextContains('css', 'div.field--name-title > div.field__item', $item->label());
  }

}
