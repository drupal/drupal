<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\UpdateFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests functionality of updating the feed in the Aggregator module.
 */
class UpdateFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Update feed functionality',
      'description' => 'Update feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Creates a feed and attempts to update it.
   */
  function testUpdateFeed() {
    $remamining_fields = array('title', 'url', '');
    foreach ($remamining_fields as $same_field) {
      $feed = $this->createFeed();

      // Get new feed data array and modify newly created feed.
      $edit = $this->getFeedEditArray();
      $edit['refresh'] =  1800; // Change refresh value.
      if (isset($feed->{$same_field}->value)) {
        $edit[$same_field] = $feed->{$same_field}->value;
      }
      $this->drupalPostForm('admin/config/services/aggregator/edit/feed/' . $feed->id(), $edit, t('Save'));
      $this->assertRaw(t('The feed %name has been updated.', array('%name' => $edit['title'])), format_string('The feed %name has been updated.', array('%name' => $edit['title'])));

      // Check feed data.
      $this->assertEqual($this->getUrl(), url('admin/config/services/aggregator', array('absolute' => TRUE)));
      $this->assertTrue($this->uniqueFeed($edit['title'], $edit['url']), 'The feed is unique.');

      // Check feed source.
      $this->drupalGet('aggregator/sources/' . $feed->id());
      $this->assertResponse(200, 'Feed source exists.');
      $this->assertText($edit['title'], 'Page title');

      // Delete feed.
      $feed->title = $edit['title']; // Set correct title so deleteFeed() will work.
      $this->deleteFeed($feed);
    }
  }
}
