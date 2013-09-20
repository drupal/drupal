<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\CategorizeFeedItemTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests categorization functionality in the Aggregator module.
 */
class CategorizeFeedItemTest extends AggregatorTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Categorize feed item functionality',
      'description' => 'Test feed item categorization.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Checks that children of a feed inherit a defined category.
   *
   * If a feed has a category, make sure that the children inherit that
   * categorization.
   */
  function testCategorizeFeedItem() {
    $this->createSampleNodes();

    // Simulate form submission on "admin/config/services/aggregator/add/category".
    $edit = array('title' => $this->randomName(10), 'description' => '');
    $this->drupalPostForm('admin/config/services/aggregator/add/category', $edit, t('Save'));
    $this->assertRaw(t('The category %title has been added.', array('%title' => $edit['title'])), format_string('The category %title has been added.', array('%title' => $edit['title'])));

    $category = db_query("SELECT * FROM {aggregator_category} WHERE title = :title", array(':title' => $edit['title']))->fetch();
    $this->assertTrue(!empty($category), 'The category found in database.');

    $link_path = 'aggregator/categories/' . $category->cid;
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => $link_path));
    $this->assertTrue(!empty($menu_links), 'The menu link associated with the category found in database.');

    $feed = $this->createFeed();
    db_insert('aggregator_category_feed')
      ->fields(array(
        'cid' => $category->cid,
        'fid' => $feed->id(),
      ))
      ->execute();

    $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());
    $this->getFeedCategories($feed);
    $this->assertTrue(!empty($feed->categories), 'The category found in the feed.');

    // For each category of a feed, ensure feed items have that category, too.
    if (!empty($feed->categories) && !empty($feed->items)) {
      foreach ($feed->categories as $cid) {
        $categorized_count = db_select('aggregator_category_item')
          ->condition('iid', $feed->items, 'IN')
          ->countQuery()
          ->execute()
          ->fetchField();

        $this->assertEqual($feed->item_count, $categorized_count, 'Total items in feed equal to the total categorized feed items in database');
      }
    }

    // Place a category block.
    $block = $this->drupalPlaceBlock("aggregator_category_block", array('label' => 'category-' . $category->title));

    // Configure the feed that should be displayed.
    $block->getPlugin()->setConfigurationValue('cid', $category->cid);
    $block->save();

    // Visit the frontpage, assert that the block and the feeds are displayed.
    $this->drupalGet('');
    $this->assertText('category-' . $category->title);
    foreach (\Drupal::entityManager()->getStorageController('aggregator_item')->loadMultiple() as $item) {
      $this->assertText($item->label());
    }

    // Delete feed.
    $this->deleteFeed($feed);
  }
}
