<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedLanguageTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests aggregator feeds in multiple languages.
 */
class FeedLanguageTest extends AggregatorTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * List of langcodes.
   *
   * @var array
   */
  protected $langcodes = array();

  public static function getInfo() {
    return array(
      'name' => 'Multilingual feeds',
      'description' => 'Checks creating of feeds in multiple languages',
      'group' => 'Aggregator',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create test languages.
    $this->langcodes = array(language_load('en'));
    for ($i = 1; $i < 3; ++$i) {
      $language = new Language(array(
        'id' => 'l' . $i,
        'name' => $this->randomString(),
      ));
      language_save($language);
      $this->langcodes[$i] = $language;
    }
  }

  /**
   * Tests creation of feeds with a language.
   */
  public function testFeedLanguage() {
    $feeds = array();
    // Create feeds.
    $feeds[1] = $this->createFeed(NULL, array('langcode' => $this->langcodes[1]->id));
    $feeds[2] = $this->createFeed(NULL, array('langcode' => $this->langcodes[2]->id));

    // Make sure that the language has been assigned.
    $this->assertEqual($feeds[1]->language()->id, $this->langcodes[1]->id);
    $this->assertEqual($feeds[2]->language()->id, $this->langcodes[2]->id);

    // Create example nodes to create feed items from and then update the feeds.
    $this->createSampleNodes();
    $this->cronRun();

    // Loop over the created feed items and verify that their language matches
    // the one from the feed.
    foreach ($feeds as $feed) {
      $items = entity_load_multiple_by_properties('aggregator_item', array('fid' => $feed->id()));
      $this->assertTrue(count($items) > 0, 'Feed items were created.');
      foreach ($items as $item) {
        $this->assertEqual($item->language()->id, $feed->language()->id);
      }
    }
  }
}
