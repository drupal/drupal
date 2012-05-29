<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\UpdateFeedItemTest.
 */

namespace Drupal\aggregator\Tests;

class UpdateFeedItemTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Update feed item functionality',
      'description' => 'Update feed items from a feed.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Test running "update items" from the 'admin/config/services/aggregator' page.
   */
  function testUpdateFeedItem() {
    $this->createSampleNodes();

    // Create a feed and test updating feed items if possible.
    $feed = $this->createFeed();
    if (!empty($feed)) {
      $this->updateFeedItems($feed, $this->getDefaultFeedItemCount());
      $this->removeFeedItems($feed);
    }

    // Delete feed.
    $this->deleteFeed($feed);

    // Test updating feed items without valid timestamp information.
    $edit = array(
      'title' => "Feed without publish timestamp",
      'url' => $this->getRSS091Sample(),
    );

    $this->drupalGet($edit['url']);
    $this->assertResponse(array(200), t('URL !url is accessible', array('!url' => $edit['url'])));

    $this->drupalPost('admin/config/services/aggregator/add/feed', $edit, t('Save'));
    $this->assertRaw(t('The feed %name has been added.', array('%name' => $edit['title'])), t('The feed !name has been added.', array('!name' => $edit['title'])));

    $feed = db_query("SELECT * FROM {aggregator_feed} WHERE url = :url", array(':url' => $edit['url']))->fetchObject();

    aggregator_refresh($feed);
    $before = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid))->fetchField();

    // Sleep for 3 second.
    sleep(3);
    db_update('aggregator_feed')
      ->condition('fid', $feed->fid)
      ->fields(array(
        'checked' => 0,
        'hash' => '',
        'etag' => '',
        'modified' => 0,
      ))
      ->execute();
    aggregator_refresh($feed);

    $after = db_query('SELECT timestamp FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid))->fetchField();
    $this->assertTrue($before === $after, t('Publish timestamp of feed item was not updated (!before === !after)', array('!before' => $before, '!after' => $after)));
  }
}
