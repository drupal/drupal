<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\FeedParserTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests for feed parsing.
 */
class FeedParserTest extends AggregatorTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Feed parser functionality',
      'description' => 'Test the built-in feed parser with valid feed samples.',
      'group' => 'Aggregator',
    );
  }

  function setUp() {
    parent::setUp();
    // Do not remove old aggregator items during these tests, since our sample
    // feeds have hardcoded dates in them (which may be expired when this test
    // is run).
    config('aggregator.settings')->set('items.expire', AGGREGATOR_CLEAR_NEVER)->save();
  }

  /**
   * Test a feed that uses the RSS 0.91 format.
   */
  function testRSS091Sample() {
    $feed = $this->createFeed($this->getRSS091Sample());
    aggregator_refresh($feed);
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->title)));
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
   * Test a feed that uses the Atom format.
   */
  function testAtomSample() {
    $feed = $this->createFeed($this->getAtomSample());
    aggregator_refresh($feed);
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->title)));
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
    aggregator_refresh($feed);
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, format_string('Feed %name exists.', array('%name' => $feed->title)));
    $this->assertRaw("Quote&quot; Amp&amp;");
  }
}
