<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;

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
    $this->assertText(t('The feed @name has been added.', ['@name' => $edit['title[0][value]']]), new FormattableMarkup('The feed @name has been added.', ['@name' => $edit['title[0][value]']]));

    // Verify that the creation message contains a link to a feed.
    $view_link = $this->xpath('//div[@class="messages"]//a[contains(@href, :href)]', [':href' => 'aggregator/sources/']);
    $this->assert(isset($view_link), 'The message area contains a link to a feed');

    $fids = \Drupal::entityQuery('aggregator_feed')->condition('url', $edit['url[0][value]'])->execute();
    $feed = Feed::load(array_values($fids)[0]);

    $feed->refreshItems();
    $iids = \Drupal::entityQuery('aggregator_item')->condition('fid', $feed->id())->execute();
    $before = Item::load(array_values($iids)[0])->getPostedTime();

    // Sleep for 3 second.
    sleep(3);
    $feed
      ->setLastCheckedTime(0)
      ->setHash('')
      ->setEtag('')
      ->setLastModified(0)
      ->save();
    $feed->refreshItems();

    $after = Item::load(array_values($iids)[0])->getPostedTime();
    $this->assertTrue($before === $after, new FormattableMarkup('Publish timestamp of feed item was not updated (@before === @after)', ['@before' => $before, '@after' => $after]));

    // Make sure updating items works even after uninstalling a module
    // that provides the selected plugins.
    $this->enableTestPlugins();
    $this->container->get('module_installer')->uninstall(['aggregator_test']);
    $this->updateFeedItems($feed);
    $this->assertResponse(200);
  }

}
