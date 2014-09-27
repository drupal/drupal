<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\UpdateFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Update feed test.
 *
 * @group aggregator
 */
class UpdateFeedTest extends AggregatorTestBase {
  /**
   * Creates a feed and attempts to update it.
   */
  function testUpdateFeed() {
    $remaining_fields = array('title[0][value]', 'url[0][value]', '');
    foreach ($remaining_fields as $same_field) {
      $feed = $this->createFeed();

      // Get new feed data array and modify newly created feed.
      $edit = $this->getFeedEditArray();
      $edit['refresh'] =  1800; // Change refresh value.
      if (isset($feed->{$same_field}->value)) {
        $edit[$same_field] = $feed->{$same_field}->value;
      }
      $this->drupalPostForm('aggregator/sources/' . $feed->id() . '/configure', $edit, t('Save'));
      $this->assertRaw(t('The feed %name has been updated.', array('%name' => $edit['title[0][value]'])), format_string('The feed %name has been updated.', array('%name' => $edit['title[0][value]'])));

      // Check feed data.
      $this->assertUrl($feed->url('canonical', ['absolute' => TRUE]));
      $this->assertTrue($this->uniqueFeed($edit['title[0][value]'], $edit['url[0][value]']), 'The feed is unique.');

      // Check feed source.
      $this->drupalGet('aggregator/sources/' . $feed->id());
      $this->assertResponse(200, 'Feed source exists.');
      $this->assertText($edit['title[0][value]'], 'Page title');

      // Delete feed.
      $feed->title = $edit['title[0][value]']; // Set correct title so deleteFeed() will work.
      $this->deleteFeed($feed);
    }
  }
}
