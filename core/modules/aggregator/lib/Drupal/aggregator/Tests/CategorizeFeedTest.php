<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\CategorizeFeedTest.
 */

namespace Drupal\aggregator\Tests;

class CategorizeFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Categorize feed functionality',
      'description' => 'Categorize feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Create a feed and make sure you can add more than one category to it.
   */
  function testCategorizeFeed() {

    // Create 2 categories.
    $category_1 = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPost('admin/config/services/aggregator/add/category', $category_1, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $category_1['title'])), t('The category %title has been added.', array('%title' => $category_1['title'])));

    $category_2 = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPost('admin/config/services/aggregator/add/category', $category_2, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $category_2['title'])), t('The category %title has been added.', array('%title' => $category_2['title'])));

    // Get categories from database.
    $categories = $this->getCategories();

    // Create a feed and assign 2 categories to it.
    $feed = $this->getFeedEditArray();
    $feed['block'] = 5;
    foreach ($categories as $cid => $category) {
      $feed['category'][$cid] = $cid;
    }

    // Use aggregator_save_feed() function to save the feed.
    aggregator_save_feed($feed);
    $db_feed = db_query("SELECT *  FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed['title'], ':url' => $feed['url']))->fetch();

    // Assert the feed has two categories.
    $this->getFeedCategories($db_feed);
    $this->assertEqual(count($db_feed->categories), 2, t('Feed has 2 categories'));
  }
}
