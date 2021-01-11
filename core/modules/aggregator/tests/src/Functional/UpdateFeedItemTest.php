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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm('aggregator/sources/add', $edit, 'Save');
    $this->assertText('The feed ' . $edit['title[0][value]'] . ' has been added.');

    // Verify that the creation message contains a link to a feed.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "aggregator/sources/")]');

    $fids = \Drupal::entityQuery('aggregator_feed')->condition('url', $edit['url[0][value]'])->execute();
    $feed = Feed::load(array_values($fids)[0]);

    $feed->refreshItems();
    $item_ids = \Drupal::entityQuery('aggregator_item')->condition('fid', $feed->id())->execute();
    $before = Item::load(array_values($item_ids)[0])->getPostedTime();

    // Sleep for 3 second.
    sleep(3);
    $feed
      ->setLastCheckedTime(0)
      ->setHash('')
      ->setEtag('')
      ->setLastModified(0)
      ->save();
    $feed->refreshItems();

    $after = Item::load(array_values($item_ids)[0])->getPostedTime();
    $this->assertTrue($before === $after, new FormattableMarkup('Publish timestamp of feed item was not updated (@before === @after)', ['@before' => $before, '@after' => $after]));

    // Make sure updating items works even after uninstalling a module
    // that provides the selected plugins.
    $this->enableTestPlugins();
    $this->container->get('module_installer')->uninstall(['aggregator_test']);
    $this->updateFeedItems($feed);
    $this->assertSession()->statusCodeEquals(200);
  }

}
