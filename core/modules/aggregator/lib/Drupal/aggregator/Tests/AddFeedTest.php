<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AddFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests aggregator feed adding.
 */
class AddFeedTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Add feed functionality',
      'description' => 'Add feed test.',
      'group' => 'Aggregator'
    );
  }

  /**
   * Create a feed, ensure that it is unique, check the source, and delete the feed.
   */
  function testAddFeed() {
    $feed = $this->createFeed();

    // Check feed data.
    $this->assertEqual($this->getUrl(), url('admin/config/services/aggregator/add/feed', array('absolute' => TRUE)), t('Directed to correct url.'));
    $this->assertTrue($this->uniqueFeed($feed->title, $feed->url), t('The feed is unique.'));

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, t('Feed source exists.'));
    $this->assertText($feed->title, t('Page title'));
    $this->drupalGet('aggregator/sources/' . $feed->fid . '/categorize');
    $this->assertResponse(200, t('Feed categorization page exists.'));

    // Delete feed.
    $this->deleteFeed($feed);
  }

  /**
   * Tests feeds with very long URLs.
   */
  function testAddLongFeed() {
    // Create a feed with a URL of > 255 characters.
    $long_url = "https://www.google.com/search?ix=heb&sourceid=chrome&ie=UTF-8&q=angie+byron#sclient=psy-ab&hl=en&safe=off&source=hp&q=angie+byron&pbx=1&oq=angie+byron&aq=f&aqi=&aql=&gs_sm=3&gs_upl=0l0l0l10534l0l0l0l0l0l0l0l0ll0l0&bav=on.2,or.r_gc.r_pw.r_cp.,cf.osb&fp=a70b6b1f0abe28d8&biw=1629&bih=889&ix=heb";
    $feed = $this->createFeed($long_url);

    // Create a second feed of > 255 characters, where the only difference is
    // after the 255th character.
    $long_url_2 = "https://www.google.com/search?ix=heb&sourceid=chrome&ie=UTF-8&q=angie+byron#sclient=psy-ab&hl=en&safe=off&source=hp&q=angie+byron&pbx=1&oq=angie+byron&aq=f&aqi=&aql=&gs_sm=3&gs_upl=0l0l0l10534l0l0l0l0l0l0l0l0ll0l0&bav=on.2,or.r_gc.r_pw.r_cp.,cf.osb&fp=a70b6b1f0abe28d8&biw=1629&bih=889";
    $feed_2 = $this->createFeed($long_url_2);

    // Check feed data.
    $this->assertTrue($this->uniqueFeed($feed->title, $feed->url), 'The first long URL feed is unique.');
    $this->assertTrue($this->uniqueFeed($feed_2->title, $feed_2->url), 'The second long URL feed is unique.');

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, 'Long URL feed source exists.');
    $this->assertText($feed->title, 'Page title');
    $this->drupalGet('aggregator/sources/' . $feed->fid . '/categorize');
    $this->assertResponse(200, 'Long URL feed categorization page exists.');

    // Delete feeds.
    $this->deleteFeed($feed);
    $this->deleteFeed($feed_2);
  }
}
