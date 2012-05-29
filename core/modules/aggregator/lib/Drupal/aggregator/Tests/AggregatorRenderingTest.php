<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AggregatorRenderingTest.
 */

namespace Drupal\aggregator\Tests;

class AggregatorRenderingTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Checks display of aggregator items',
      'description' => 'Checks display of aggregator items on the page.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Add a feed block to the page and checks its links.
   *
   * TODO: Test the category block as well.
   */
  public function testBlockLinks() {
    // Create feed.
    $this->createSampleNodes();
    $feed = $this->createFeed();
    $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());

    // Place block on page (@see block.test:moveBlockToRegion())
    // Need admin user to be able to access block admin.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
      'administer news feeds',
      'access news feeds',
    ));
    $this->drupalLogin($this->admin_user);

    // Prepare to use the block admin form.
    $block = array(
      'module' => 'aggregator',
      'delta' => 'feed-' . $feed->fid,
      'title' => $feed->title,
    );
    $region = 'footer';
    $edit = array();
    $edit['blocks[' . $block['module'] . '_' . $block['delta'] . '][region]'] = $region;
    // Check the feed block is available in the block list form.
    $this->drupalGet('admin/structure/block');
    $this->assertFieldByName('blocks[' . $block['module'] . '_' . $block['delta'] . '][region]', '', 'Aggregator feed block is available for positioning.');
    // Position it.
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), t('Block successfully moved to %region_name region.', array( '%region_name' => $region)));
    // Confirm that the block is now being displayed on pages.
    $this->drupalGet('node');
    $this->assertText(t($block['title']), t('Feed block is displayed on the page.'));

    // Find the expected read_more link.
    $href = 'aggregator/sources/' . $feed->fid;
    $links = $this->xpath('//a[@href = :href]', array(':href' => url($href)));
    $this->assert(isset($links[0]), t('Link to href %href found.', array('%href' => $href)));

    // Visit that page.
    $this->drupalGet($href);
    $correct_titles = $this->xpath('//h1[normalize-space(text())=:title]', array(':title' => $feed->title));
    $this->assertFalse(empty($correct_titles), t('Aggregator feed page is available and has the correct title.'));

    // Set the number of news items to 0 to test that the block does not show
    // up.
    $feed->block = 0;
    aggregator_save_feed((array) $feed);
    // It is nescessary to flush the cache after saving the number of items.
    $this->resetAll();
    // Check that the block is no longer displayed.
    $this->drupalGet('node');
    $this->assertNoText(t($block['title']), 'Feed block is not displayed on the page when number of items is set to 0.');
  }

  /**
   * Create a feed and check that feed's page.
   */
  public function testFeedPage() {
    // Increase the number of items published in the rss.xml feed so we have
    // enough articles to test paging.
    $config = config('system.rss-publishing');
    $config->set('feed_default_items', 30);
    $config->save();

    // Create a feed with 30 items.
    $this->createSampleNodes(30);
    $feed = $this->createFeed();
    $this->updateFeedItems($feed, 30);

    // Check for the presence of a pager.
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $elements = $this->xpath("//ul[@class=:class]", array(':class' => 'pager'));
    $this->assertTrue(!empty($elements), t('Individual source page contains a pager.'));

    // Reset the number of items in rss.xml to the default value.
    $config->set('feed_default_items', 10);
    $config->save();
  }
}
