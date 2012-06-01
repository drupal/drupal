<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\UpdateFeedTest.
 */

namespace Drupal\aggregator\Tests;

class UpdateFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Update feed functionality',
      'description' => 'Update feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Create a feed and attempt to update it.
   */
  function testUpdateFeed() {
    $remamining_fields = array('title', 'url', '');
    foreach ($remamining_fields as $same_field) {
      $feed = $this->createFeed();

      // Get new feed data array and modify newly created feed.
      $edit = $this->getFeedEditArray();
      $edit['refresh'] =  1800; // Change refresh value.
      if (isset($feed->{$same_field})) {
        $edit[$same_field] = $feed->{$same_field};
      }
      $this->drupalPost('admin/config/services/aggregator/edit/feed/' . $feed->fid, $edit, t('Save'));
      $this->assertRaw(t('The feed %name has been updated.', array('%name' => $edit['title'])), t('The feed %name has been updated.', array('%name' => $edit['title'])));

      // Check feed data.
      $this->assertEqual($this->getUrl(), url('admin/config/services/aggregator/', array('absolute' => TRUE)));
      $this->assertTrue($this->uniqueFeed($edit['title'], $edit['url']), t('The feed is unique.'));

      // Check feed source.
      $this->drupalGet('aggregator/sources/' . $feed->fid);
      $this->assertResponse(200, t('Feed source exists.'));
      $this->assertText($edit['title'], t('Page title'));

      // Delete feed.
      $feed->title = $edit['title']; // Set correct title so deleteFeed() will work.
      $this->deleteFeed($feed);
    }
  }
}
