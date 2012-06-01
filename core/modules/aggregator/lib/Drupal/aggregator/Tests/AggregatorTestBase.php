<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorTestBase.
 */

namespace Drupal\aggregator\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for testing aggregator.module.
 */
class AggregatorTestBase extends WebTestBase {
  function setUp() {
    parent::setUp(array('node', 'block', 'aggregator', 'aggregator_test'));

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    $web_user = $this->drupalCreateUser(array('administer news feeds', 'access news feeds', 'create article content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Create an aggregator feed (simulate form submission on admin/config/services/aggregator/add/feed).
   *
   * @param $feed_url
   *   If given, feed will be created with this URL, otherwise /rss.xml will be used.
   * @return $feed
   *   Full feed object if possible.
   *
   * @see getFeedEditArray()
   */
  function createFeed($feed_url = NULL) {
    $edit = $this->getFeedEditArray($feed_url);
    $this->drupalPost('admin/config/services/aggregator/add/feed', $edit, t('Save'));
    $this->assertRaw(t('The feed %name has been added.', array('%name' => $edit['title'])), t('The feed !name has been added.', array('!name' => $edit['title'])));

    $feed = db_query("SELECT *  FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $edit['title'], ':url' => $edit['url']))->fetch();
    $this->assertTrue(!empty($feed), t('The feed found in database.'));
    return $feed;
  }

  /**
   * Delete an aggregator feed.
   *
   * @param $feed
   *   Feed object representing the feed.
   */
  function deleteFeed($feed) {
    $this->drupalPost('admin/config/services/aggregator/edit/feed/' . $feed->fid, array(), t('Delete'));
    $this->assertRaw(t('The feed %title has been deleted.', array('%title' => $feed->title)), t('Feed deleted successfully.'));
  }

  /**
   * Return a randomly generated feed edit array.
   *
   * @param $feed_url
   *   If given, feed will be created with this URL, otherwise /rss.xml will be used.
   * @return
   *   A feed array.
   */
  function getFeedEditArray($feed_url = NULL) {
    $feed_name = $this->randomName(10);
    if (!$feed_url) {
      $feed_url = url('rss.xml', array(
        'query' => array('feed' => $feed_name),
        'absolute' => TRUE,
      ));
    }
    $edit = array(
      'title' => $feed_name,
      'url' => $feed_url,
      'refresh' => '900',
    );
    return $edit;
  }

  /**
   * Return the count of the randomly created feed array.
   *
   * @return
   *   Number of feed items on default feed created by createFeed().
   */
  function getDefaultFeedItemCount() {
    // Our tests are based off of rss.xml, so let's find out how many elements should be related.
    $feed_count = db_query_range('SELECT COUNT(*) FROM {node} n WHERE n.promote = 1 AND n.status = 1', 0, config('system.rss-publishing')->get('feed_default_items'))->fetchField();
    return $feed_count > 10 ? 10 : $feed_count;
  }

  /**
   * Update feed items (simulate click to admin/config/services/aggregator/update/$fid).
   *
   * @param $feed
   *   Feed object representing the feed.
   * @param $expected_count
   *   Expected number of feed items.
   */
  function updateFeedItems(&$feed, $expected_count) {
    // First, let's ensure we can get to the rss xml.
    $this->drupalGet($feed->url);
    $this->assertResponse(200, t('!url is reachable.', array('!url' => $feed->url)));

    // Attempt to access the update link directly without an access token.
    $this->drupalGet('admin/config/services/aggregator/update/' . $feed->fid);
    $this->assertResponse(403);

    // Refresh the feed (simulated link click).
    $this->drupalGet('admin/config/services/aggregator');
    $this->clickLink('update items');

    // Ensure we have the right number of items.
    $result = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid));
    $items = array();
    $feed->items = array();
    foreach ($result as $item) {
      $feed->items[] = $item->iid;
    }
    $feed->item_count = count($feed->items);
    $this->assertEqual($expected_count, $feed->item_count, t('Total items in feed equal to the total items in database (!val1 != !val2)', array('!val1' => $expected_count, '!val2' => $feed->item_count)));
  }

  /**
   * Confirm item removal from a feed.
   *
   * @param $feed
   *   Feed object representing the feed.
   */
  function removeFeedItems($feed) {
    $this->drupalPost('admin/config/services/aggregator/remove/' . $feed->fid, array(), t('Remove items'));
    $this->assertRaw(t('The news items from %title have been removed.', array('%title' => $feed->title)), t('Feed items removed.'));
  }

  /**
   * Add and remove feed items and ensure that the count is zero.
   *
   * @param $feed
   *   Feed object representing the feed.
   * @param $expected_count
   *   Expected number of feed items.
   */
  function updateAndRemove($feed, $expected_count) {
    $this->updateFeedItems($feed, $expected_count);
    $count = db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid))->fetchField();
    $this->assertTrue($count);
    $this->removeFeedItems($feed);
    $count = db_query('SELECT COUNT(*) FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid))->fetchField();
    $this->assertTrue($count == 0);
  }

  /**
   * Pull feed categories from aggregator_category_feed table.
   *
   * @param $feed
   *   Feed object representing the feed.
   */
  function getFeedCategories($feed) {
    // add the categories to the feed so we can use them
    $result = db_query('SELECT cid FROM {aggregator_category_feed} WHERE fid = :fid', array(':fid' => $feed->fid));
    foreach ($result as $category) {
      $feed->categories[] = $category->cid;
    }
  }

  /**
   * Pull categories from aggregator_category table.
   */
  function getCategories() {
    $categories = array();
    $result = db_query('SELECT * FROM {aggregator_category}');
    foreach ($result as $category) {
      $categories[$category->cid] = $category;
    }
    return $categories;
  }


  /**
   * Check if the feed name and url is unique.
   *
   * @param $feed_name
   *   String containing the feed name to check.
   * @param $feed_url
   *   String containing the feed url to check.
   * @return
   *   TRUE if feed is unique.
   */
  function uniqueFeed($feed_name, $feed_url) {
    $result = db_query("SELECT COUNT(*) FROM {aggregator_feed} WHERE title = :title AND url = :url", array(':title' => $feed_name, ':url' => $feed_url))->fetchField();
    return (1 == $result);
  }

  /**
   * Create a valid OPML file from an array of feeds.
   *
   * @param $feeds
   *   An array of feeds.
   * @return
   *   Path to valid OPML file.
   */
  function getValidOpml($feeds) {
    // Properly escape URLs so that XML parsers don't choke on them.
    foreach ($feeds as &$feed) {
      $feed['url'] = htmlspecialchars($feed['url']);
    }
    /**
     * Does not have an XML declaration, must pass the parser.
     */
    $opml = <<<EOF
<opml version="1.0">
  <head></head>
  <body>
    <!-- First feed to be imported. -->
    <outline text="{$feeds[0]['title']}" xmlurl="{$feeds[0]['url']}" />

    <!-- Second feed. Test string delimitation and attribute order. -->
    <outline xmlurl='{$feeds[1]['url']}' text='{$feeds[1]['title']}'/>

    <!-- Test for duplicate URL and title. -->
    <outline xmlurl="{$feeds[0]['url']}" text="Duplicate URL"/>
    <outline xmlurl="http://duplicate.title" text="{$feeds[1]['title']}"/>

    <!-- Test that feeds are only added with required attributes. -->
    <outline text="{$feeds[2]['title']}" />
    <outline xmlurl="{$feeds[2]['url']}" />
  </body>
</opml>
EOF;

    $path = 'public://valid-opml.xml';
    return file_unmanaged_save_data($opml, $path);
  }

  /**
   * Create an invalid OPML file.
   *
   * @return
   *   Path to invalid OPML file.
   */
  function getInvalidOpml() {
    $opml = <<<EOF
<opml>
  <invalid>
</opml>
EOF;

    $path = 'public://invalid-opml.xml';
    return file_unmanaged_save_data($opml, $path);
  }

  /**
   * Create a valid but empty OPML file.
   *
   * @return
   *   Path to empty OPML file.
   */
  function getEmptyOpml() {
    $opml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<opml version="1.0">
  <head></head>
  <body>
    <outline text="Sample text" />
    <outline text="Sample text" url="Sample URL" />
  </body>
</opml>
EOF;

    $path = 'public://empty-opml.xml';
    return file_unmanaged_save_data($opml, $path);
  }

  function getRSS091Sample() {
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/aggregator_test_rss091.xml';
  }

  function getAtomSample() {
    // The content of this sample ATOM feed is based directly off of the
    // example provided in RFC 4287.
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/aggregator_test_atom.xml';
  }

  function getHtmlEntitiesSample() {
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/aggregator_test_title_entities.xml';
  }

  /**
   * Creates sample article nodes.
   *
   * @param $count
   *   (optional) The number of nodes to generate.
   */
  function createSampleNodes($count = 5) {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    // Post $count article nodes.
    for ($i = 0; $i < $count; $i++) {
      $edit = array();
      $edit['title'] = $this->randomName();
      $edit["body[$langcode][0][value]"] = $this->randomName();
      $this->drupalPost('node/add/article', $edit, t('Save'));
    }
  }
}
