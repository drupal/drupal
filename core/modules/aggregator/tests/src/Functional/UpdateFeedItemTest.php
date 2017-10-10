<?php

namespace Drupal\Tests\aggregator\Functional;

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
    $edit = [
      'title[0][value]' => "Feed without publish timestamp",
      'url[0][value]' => $this->getRSS091Sample(),
    ];

    $this->drupalGet($edit['url[0][value]']);
    $this->assertResponse(200);

    $this->drupalPostForm('aggregator/sources/add', $edit, t('Save'));
    $this->assertText(t('The feed @name has been added.', ['@name' => $edit['title[0][value]']]), format_string('The feed @name has been added.', ['@name' => $edit['title[0][value]']]));

    // Verify that the creation message contains a link to a feed.
    $view_link = $this->xpath('//div[@class="messages"]//a[contains(@href, :href)]', [':href' => 'aggregator/sources/']);
    $this->assert(isset($view_link), 'The message area contains a link to a feed');

    $fid = db_query("SELECT fid FROM {aggregator_feed} WHERE url = :url", [':url' => $edit['url[0][value]']])->fetchField();
    $feed = Feed::load($fid);

    $feed->refreshItems();
    $before = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField();

    // Sleep for 3 second.
    sleep(3);
    db_update('aggregator_feed')
      ->condition('fid', $feed->id())
      ->fields([
        'checked' => 0,
        'hash' => '',
        'etag' => '',
        'modified' => 0,
      ])
      ->execute();
    $feed->refreshItems();

    $after = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', [':fid' => $feed->id()])->fetchField();
    $this->assertTrue($before === $after, format_string('Publish timestamp of feed item was not updated (@before === @after)', ['@before' => $before, '@after' => $after]));

    // Make sure updating items works even after uninstalling a module
    // that provides the selected plugins.
    $this->enableTestPlugins();
    $this->container->get('module_installer')->uninstall(['aggregator_test']);
    $this->updateFeedItems($feed);
    $this->assertResponse(200);
  }

}
