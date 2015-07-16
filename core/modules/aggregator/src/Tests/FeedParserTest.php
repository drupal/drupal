<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedParserTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\Core\Url;
use Zend\Feed\Reader\Reader;

/**
 * Tests the built-in feed parser with valid feed samples.
 *
 * @group aggregator
 */
class FeedParserTest extends AggregatorTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Do not delete old aggregator items during these tests, since our sample
    // feeds have hardcoded dates in them (which may be expired when this test
    // is run).
    $this->config('aggregator.settings')->set('items.expire', AGGREGATOR_CLEAR_NEVER)->save();
  }

  /**
   * Tests a feed that uses the RSS 0.91 format.
   */
  public function testRSS091Sample() {
    $feed = $this->createFeed($this->getRSS091Sample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertText('First example feed item title');
    $this->assertLinkByHref('http://example.com/example-turns-one');
    $this->assertText('First example feed item description.');
    $this->assertRaw('<img src="http://example.com/images/druplicon.png"');

    // Several additional items that include elements over 255 characters.
    $this->assertRaw("Second example feed item title.");
    $this->assertText('Long link feed item title');
    $this->assertText('Long link feed item description');
    $this->assertLinkByHref('http://example.com/tomorrow/and/tomorrow/and/tomorrow/creeps/in/this/petty/pace/from/day/to/day/to/the/last/syllable/of/recorded/time/and/all/our/yesterdays/have/lighted/fools/the/way/to/dusty/death/out/out/brief/candle/life/is/but/a/walking/shadow/a/poor/player/that/struts/and/frets/his/hour/upon/the/stage/and/is/heard/no/more/it/is/a/tale/told/by/an/idiot/full/of/sound/and/fury/signifying/nothing');
    $this->assertText('Long author feed item title');
    $this->assertText('Long author feed item description');
    $this->assertLinkByHref('http://example.com/long/author');
  }

  /**
   * Tests a feed that uses the Atom format.
   */
  public function testAtomSample() {
    $feed = $this->createFeed($this->getAtomSample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertText('Atom-Powered Robots Run Amok');
    $this->assertLinkByHref('http://example.org/2003/12/13/atom03');
    $this->assertText('Some text.');
    $this->assertEqual('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', db_query('SELECT guid FROM {aggregator_item} WHERE link = :link', array(':link' => 'http://example.org/2003/12/13/atom03'))->fetchField(), 'Atom entry id element is parsed correctly.');

    // Check for second feed entry.
    $this->assertText('We tried to stop them, but we failed.');
    $this->assertLinkByHref('http://example.org/2003/12/14/atom03');
    $this->assertText('Some other text.');
    $db_guid = db_query('SELECT guid FROM {aggregator_item} WHERE link = :link', array(
      ':link' => 'http://example.org/2003/12/14/atom03',
    ))->fetchField();
    $this->assertEqual('urn:uuid:1225c695-cfb8-4ebb-bbbb-80da344efa6a', $db_guid, 'Atom entry id element is parsed correctly.');
  }

  /**
   * Tests a feed that uses HTML entities in item titles.
   */
  public function testHtmlEntitiesSample() {
    $feed = $this->createFeed($this->getHtmlEntitiesSample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertRaw("Quote&quot; Amp&amp;");
  }

  /**
   * Tests that a redirected feed is tracked to its target.
   */
  public function testRedirectFeed() {
    $redirect_url = Url::fromRoute('aggregator_test.redirect')->setAbsolute()->toString();
    $feed = entity_create('aggregator_feed', array('url' => $redirect_url, 'title' => $this->randomMachineName()));
    $feed->save();
    $feed->refreshItems();

    // Make sure that the feed URL was updated correctly.
    $this->assertEqual($feed->getUrl(), \Drupal::url('aggregator_test.feed', array(), array('absolute' => TRUE)));
  }

  /**
   * Tests error handling when an invalid feed is added.
   */
  public function testInvalidFeed() {
    // Simulate a typo in the URL to force a curl exception.
    $invalid_url = 'http:/www.drupal.org';
    $feed = entity_create('aggregator_feed', array('url' => $invalid_url, 'title' => $this->randomMachineName()));
    $feed->save();

    // Update the feed. Use the UI to be able to check the message easily.
    $this->drupalGet('admin/config/services/aggregator');
    $this->clickLink(t('Update items'));
    $this->assertRaw(t('The feed from %title seems to be broken because of error', array('%title' => $feed->label())));
  }
}
