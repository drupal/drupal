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
    variable_set('aggregator_clear', AGGREGATOR_CLEAR_NEVER);
  }

  /**
   * Test a feed that uses the RSS 0.91 format.
   */
  function testRSS091Sample() {
    $feed = $this->createFeed($this->getRSS091Sample());
    aggregator_refresh($feed);
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, t('Feed %name exists.', array('%name' => $feed->title)));
    $this->assertText('First example feed item title');
    $this->assertLinkByHref('http://example.com/example-turns-one');
    $this->assertText('First example feed item description.');
  }

  /**
   * Test a feed that uses the Atom format.
   */
  function testAtomSample() {
    $feed = $this->createFeed($this->getAtomSample());
    aggregator_refresh($feed);
    $this->drupalGet('aggregator/sources/' . $feed->fid);
    $this->assertResponse(200, t('Feed %name exists.', array('%name' => $feed->title)));
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
    $this->assertResponse(200, t('Feed %name exists.', array('%name' => $feed->title)));
    $this->assertRaw("Quote&quot; Amp&amp;");
  }
}
