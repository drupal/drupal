<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorRenderingTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests display of aggregator items on the page.
 *
 * @group aggregator
 */
class AggregatorRenderingTest extends AggregatorTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'test_page_test');

  /**
   * Adds a feed block to the page and checks its links.
   */
  public function testBlockLinks() {
    // Create feed.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());

    // Need admin user to be able to access block admin.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
      'administer news feeds',
      'access news feeds',
    ));
    $this->drupalLogin($admin_user);

    $block = $this->drupalPlaceBlock("aggregator_feed_block", array('label' => 'feed-' . $feed->label()));

    // Configure the feed that should be displayed.
    $block->getPlugin()->setConfigurationValue('feed', $feed->id());
    $block->getPlugin()->setConfigurationValue('block_count', 2);
    $block->save();

    // Confirm that the block is now being displayed on pages.
    $this->drupalGet('test-page');
    $this->assertText($block->label(), 'Feed block is displayed on the page.');

    // Find the expected read_more link.
    $href = $feed->url();
    $links = $this->xpath('//a[@href = :href]', array(':href' => $href));
    $this->assert(isset($links[0]), format_string('Link to href %href found.', array('%href' => $href)));
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $cache_tags = explode(' ', $cache_tags_header);
    $this->assertTrue(in_array('aggregator_feed:' . $feed->id(), $cache_tags));

    // Visit that page.
    $this->drupalGet($feed->urlInfo()->getInternalPath());
    $correct_titles = $this->xpath('//h1[normalize-space(text())=:title]', array(':title' => $feed->label()));
    $this->assertFalse(empty($correct_titles), 'Aggregator feed page is available and has the correct title.');
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('aggregator_feed:' . $feed->id(), $cache_tags));
    $this->assertTrue(in_array('aggregator_feed_view', $cache_tags));
    $this->assertTrue(in_array('aggregator_item_view', $cache_tags));

    // Set the number of news items to 0 to test that the block does not show
    // up.
    $block->getPlugin()->setConfigurationValue('block_count', 0);
    $block->save();
    // Check that the block is no longer displayed.
    $this->drupalGet('test-page');
    $this->assertNoText($block->label(), 'Feed block is not displayed on the page when number of items is set to 0.');
  }

  /**
   * Creates a feed and checks that feed's page.
   */
  public function testFeedPage() {
    // Increase the number of items published in the rss.xml feed so we have
    // enough articles to test paging.
    $view = entity_load('view', 'frontpage');
    $display = &$view->getDisplay('feed_1');
    $display['display_options']['pager']['options']['items_per_page'] = 30;
    $view->save();

    // Create a feed with 30 items.
    $this->createSampleNodes(30);
    $feed = $this->createFeed();
    $this->updateFeedItems($feed, 30);

    // Check for presence of an aggregator pager.
    $this->drupalGet('aggregator');
    $elements = $this->xpath("//ul[@class=:class]", array(':class' => 'pager__items'));
    $this->assertTrue(!empty($elements), 'Individual source page contains a pager.');

    // Check for sources page title.
    $this->drupalGet('aggregator/sources');
    $titles = $this->xpath('//h1[normalize-space(text())=:title]', array(':title' => 'Sources'));
    $this->assertTrue(!empty($titles), 'Source page contains correct title.');

    // Find the expected read_more link on the sources page.
    $href = $feed->url();
    $links = $this->xpath('//a[@href = :href]', array(':href' => $href));
    $this->assertTrue(isset($links[0]), SafeMarkup::format('Link to href %href found.', array('%href' => $href)));
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $cache_tags = explode(' ', $cache_tags_header);
    $this->assertTrue(in_array('aggregator_feed:' . $feed->id(), $cache_tags));

    // Check the rss aggregator page as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('aggregator/rss');
    $this->assertResponse(403);

    // Check the rss aggregator page as admin.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('aggregator/rss');
    $this->assertResponse(200);
    $this->assertEqual($this->drupalGetHeader('Content-type'), 'application/rss+xml; charset=utf-8');

    // Check the opml aggregator page.
    $this->drupalGet('aggregator/opml');
    $outline = $this->xpath('//outline[1]');
    $this->assertEqual($outline[0]['type'], 'rss', 'The correct type attribute is used for rss OPML.');
    $this->assertEqual($outline[0]['text'], $feed->label(), 'The correct text attribute is used for rss OPML.');
    $this->assertEqual($outline[0]['xmlurl'], $feed->getUrl(), 'The correct xmlUrl attribute is used for rss OPML.');

    // Check for the presence of a pager.
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $elements = $this->xpath("//ul[@class=:class]", array(':class' => 'pager__items'));
    $this->assertTrue(!empty($elements), 'Individual source page contains a pager.');
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('aggregator_feed:' . $feed->id(), $cache_tags));
    $this->assertTrue(in_array('aggregator_feed_view', $cache_tags));
    $this->assertTrue(in_array('aggregator_item_view', $cache_tags));
  }

}
