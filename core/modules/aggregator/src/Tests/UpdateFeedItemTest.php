<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\UpdateFeedItemTest.
 */

namespace Drupal\aggregator\Tests;
use Drupal\aggregator\Entity\Feed;

/**
 * Update feed items from a feed.
 *
 * @group aggregator
 */
class UpdateFeedItemTest extends AggregatorTestBase {
  /**
   * Tests running "update items" from 'admin/config/services/aggregator' page.
   */
  public function testUpdateFeedItem() {
    $this->createSampleNodes();

    // Create a feed and test updating feed items if possible.
    $feed = $this->createFeed();
    if (!empty($feed)) {
      $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());
      $this->deleteFeedItems($feed);
    }

    // Delete feed.
    $this->deleteFeed($feed);

    // Test updating feed items without valid timestamp information.
    $edit = array(
      'title[0][value]' => "Feed without publish timestamp",
      'url[0][value]' => $this->getRSS091Sample(),
    );

    $this->drupalGet($edit['url[0][value]']);
    $this->assertResponse(array(200), format_string('URL !url is accessible', array('!url' => $edit['url[0][value]'])));

    $this->drupalPostForm('aggregator/sources/add', $edit, t('Save'));
    $this->assertRaw(t('The feed %name has been added.', array('%name' => $edit['title[0][value]'])), format_string('The feed !name has been added.', array('!name' => $edit['title[0][value]'])));

    $fid = db_query("SELECT fid FROM {aggregator_feed} WHERE url = :url", array(':url' => $edit['url[0][value]']))->fetchField();
    $feed = Feed::load($fid);

    $feed->refreshItems();
    $before = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField();

    // Sleep for 3 second.
    sleep(3);
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields(array(
        'checked' => 0,
        'hash' => '',
        'etag' => '',
        'modified' => 0,
      ))
      ->execute();
    $feed->refreshItems();

    $after = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchField();
    $this->assertTrue($before === $after, format_string('Publish timestamp of feed item was not updated (!before === !after)', array('!before' => $before, '!after' => $after)));

    // Make sure updating items works even after uninstalling a module
    // that provides the selected plugins.
    $this->enableTestPlugins();
    $this->container->get('module_installer')->uninstall(array('aggregator_test'));
    $this->updateFeedItems($feed);
    $this->assertResponse(200);
  }
}
