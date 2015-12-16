<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\ItemWithoutFeedTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\aggregator\Entity\Item;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests clean handling of an item with a missing feed ID.
 *
 * @group aggregator
 */
class ItemWithoutFeedTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
  }

  /**
   * Tests attempting to create a feed item without a feed.
   */
  public function testEntityCreation() {
    $entity = Item::create([
      'title' => t('Llama 2'),
      'path' => 'https://groups.drupal.org/',
    ]);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
  }

}
