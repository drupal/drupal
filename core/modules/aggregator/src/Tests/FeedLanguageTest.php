<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedLanguageTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests aggregator feeds in multiple languages.
 *
 * @group aggregator
 */
class FeedLanguageTest extends AggregatorTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * List of langcodes.
   *
   * @var string[]
   */
  protected $langcodes = array();

  protected function setUp() {
    parent::setUp();

    // Create test languages.
    $this->langcodes = array(ConfigurableLanguage::load('en'));
    for ($i = 1; $i < 3; ++$i) {
      $language = ConfigurableLanguage::create(array(
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ));
      $language->save();
      $this->langcodes[$i] = $language->id();
    }
  }

  /**
   * Tests creation of feeds with a language.
   */
  public function testFeedLanguage() {
    /** @var \Drupal\aggregator\FeedInterface[] $feeds */
    $feeds = array();
    // Create feeds.
    $feeds[1] = $this->createFeed(NULL, array('langcode' => $this->langcodes[1]));
    $feeds[2] = $this->createFeed(NULL, array('langcode' => $this->langcodes[2]));

    // Make sure that the language has been assigned.
    $this->assertEqual($feeds[1]->language()->getId(), $this->langcodes[1]);
    $this->assertEqual($feeds[2]->language()->getId(), $this->langcodes[2]);

    // Create example nodes to create feed items from and then update the feeds.
    $this->createSampleNodes();
    $this->cronRun();

    // Loop over the created feed items and verify that their language matches
    // the one from the feed.
    foreach ($feeds as $feed) {
      /** @var \Drupal\aggregator\ItemInterface[] $items */
      $items = entity_load_multiple_by_properties('aggregator_item', array('fid' => $feed->id()));
      $this->assertTrue(count($items) > 0, 'Feed items were created.');
      foreach ($items as $item) {
        $this->assertEqual($item->language()->getId(), $feed->language()->getId());
      }
    }
  }
}
