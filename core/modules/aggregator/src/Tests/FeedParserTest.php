<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\FeedParserTest.
 */

namespace Drupal\aggregator\Tests;

use Zend\Feed\Reader\Reader;

/**
 * Tests the built-in feed parser with valid feed samples.
 *
 * @group aggregator
 */
class FeedParserTest extends AggregatorTestBase {
  protected function setUp() {
    parent::setUp();
    // Do not delete old aggregator items during these tests, since our sample
    // feeds have hardcoded dates in them (which may be expired when this test
    // is run).
    $this->container->get('config.factory')->get('aggregator.settings')->set('items.expire', AGGREGATOR_CLEAR_NEVER)->save();
    // Reset any reader cache between tests.
    Reader::reset();
    // Set our bridge extension manager to Zend Feed.
    $bridge = $this->container->get('feed.bridge.reader');
    Reader::setExtensionManager($bridge);
  }

  /**
   * Tests a feed that uses the RSS 0.91 format.
   */
  function testRSS091Sample() {
    $feed = $this->createFeed($this->getRSS091Sample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertText('First example feed item title');
    $this->assertLinkByHref('http://example.com/example-turns-one');
    $this->assertText('First example feed item description.');

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
  function testAtomSample() {
    $feed = $this->createFeed($this->getAtomSample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertText('Atom-Powered Robots Run Amok');
    $this->assertLinkByHref('http://example.org/2003/12/13/atom03');
    $this->assertText('Some text.');
    $this->assertEqual('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', db_query('SELECT guid FROM {aggregator_item} WHERE link = :link', array(':link' => 'http://example.org/2003/12/13/atom03'))->fetchField(), 'Atom entry id element is parsed correctly.');
  }

  /**
   * Tests a feed that uses HTML entities in item titles.
   */
  function testHtmlEntitiesSample() {
    $feed = $this->createFeed($this->getHtmlEntitiesSample());
    $feed->refreshItems();
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->label())));
    $this->assertRaw("Quote&quot; Amp&amp;");
  }

  /**
   * Tests error handling when an invalid feed is added.
   */
  function testRedirectFeed() {
    // Simulate a typo in the URL to force a curl exception.
    $invalid_url = url('aggregator/redirect', array('absolute' => TRUE));
    $feed = entity_create('aggregator_feed', array('url' => $invalid_url, 'title' => $this->randomMachineName()));
    $feed->save();
    $feed->refreshItems();

    // Make sure that the feed URL was updated correctly.
    $this->assertEqual($feed->getUrl(), \Drupal::url('aggregator_test.feed', array(), array('absolute' => TRUE)));
  }

  /**
   * Tests error handling when an invalid feed is added.
   */
  function testInvalidFeed() {
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
