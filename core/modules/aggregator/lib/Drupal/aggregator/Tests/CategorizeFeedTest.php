<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\CategorizeFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests the categorize feed functionality in the Aggregator module.
 */
class CategorizeFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Categorize feed functionality',
      'description' => 'Categorize feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Creates a feed and makes sure you can add more than one category to it.
   */
  function testCategorizeFeed() {

    // Create 2 categories.
    $category_1 = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPostForm('admin/config/services/aggregator/add/category', $category_1, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $category_1['title'])), format_string('The category %title has been added.', array('%title' => $category_1['title'])));

    $category_2 = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPostForm('admin/config/services/aggregator/add/category', $category_2, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $category_2['title'])), format_string('The category %title has been added.', array('%title' => $category_2['title'])));

    // Get categories from database.
    $categories = $this->getCategories();

    // Create a feed and assign 2 categories to it.
    $feed = $this->getFeedEditObject();
    foreach ($categories as $cid => $category) {
      $feed->categories[$cid] = $cid;
    }

    $feed->save();
    $db_fid = db_query("SELECT fid FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed->label(), ':url' => $feed->url->value))->fetchField();

    $db_feed = aggregator_feed_load($db_fid);
    // Assert the feed has two categories.
    $this->assertEqual(count($db_feed->categories), 2, 'Feed has 2 categories');

    // Verify the categories overview page is correctly displayed.
    $this->drupalGet('aggregator/categories');
    $this->assertText($category_1['title']);
    $this->assertText($category_2['title']);
  }
}
