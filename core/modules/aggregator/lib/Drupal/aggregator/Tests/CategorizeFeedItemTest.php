<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\CategorizeFeedItemTest.
 */

namespace Drupal\aggregator\Tests;

class CategorizeFeedItemTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Categorize feed item functionality',
      'description' => 'Test feed item categorization.',
      'group' => 'Aggregator'
    );
  }

  /**
   * If a feed has a category, make sure that the children inherit that
   * categorization.
   */
  function testCategorizeFeedItem() {
    $this->createSampleNodes();

    // Simulate form submission on "admin/config/services/aggregator/add/category".
    $edit = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPost('admin/config/services/aggregator/add/category', $edit, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $edit['title'])), t('The category %title has been added.', array('%title' => $edit['title'])));

    $category = db_query("SELECT * FROM {aggregator_category} WHERE title = :title", array(':title' => $edit['title']))->fetch();
    $this->assertTrue(!empty($category), t('The category found in database.'));

    $link_path = 'aggregator/categories/' . $category->cid;
    $menu_link = db_query("SELECT * FROM {menu_links} WHERE link_path = :link_path", array(':link_path' => $link_path))->fetch();
    $this->assertTrue(!empty($menu_link), t('The menu link associated with the category found in database.'));

    $feed = $this->createFeed();
    db_insert('aggregator_category_feed')
      ->fields(array(
        'cid' => $category->cid,
        'fid' => $feed->fid,
      ))
      ->execute();
    $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());
    $this->getFeedCategories($feed);
    $this->assertTrue(!empty($feed->categories), t('The category found in the feed.'));

    // For each category of a feed, ensure feed items have that category, too.
    if (!empty($feed->categories) && !empty($feed->items)) {
      foreach ($feed->categories as $category) {
        $categorized_count = db_select('aggregator_category_item')
          ->condition('iid', $feed->items, 'IN')
          ->countQuery()
          ->execute()
          ->fetchField();

        $this->assertEqual($feed->item_count, $categorized_count, t('Total items in feed equal to the total categorized feed items in database'));
      }
    }

    // Delete feed.
    $this->deleteFeed($feed);
  }
}
