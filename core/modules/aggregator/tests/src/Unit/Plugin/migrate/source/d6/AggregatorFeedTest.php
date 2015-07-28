<?php

/**
 * @file
 * Contains \Drupal\Tests\aggregator\Unit\Plugin\migrate\source\d6\AggregatorFeedTest.
 */

namespace Drupal\Tests\aggregator\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 aggregator feed source plugin.
 *
 * @group aggregator
 */
class AggregatorFeedTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\aggregator\Plugin\migrate\source\d6\AggregatorFeed';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_aggregator_feed',
    ),
  );

  protected $expectedResults = array(
    array(
      'fid' => 1,
      'title' => 'feed title 1',
      'url' => 'http://example.com/feed.rss',
      'refresh' => 900,
      'checked' => 0,
      'link' => 'http://example.com',
      'description' => 'A vague description',
      'image' => '',
      'etag' => '',
      'modified' => 0,
      'block' => 5,
    ),
    array(
      'fid' => 2,
      'title' => 'feed title 2',
      'url' => 'http://example.net/news.rss',
      'refresh' => 1800,
      'checked' => 0,
      'link' => 'http://example.net',
      'description' => 'An even more vague description',
      'image' => '',
      'etag' => '',
      'modified' => 0,
      'block' => 5,
    ),
  );

  /**
  * {@inheritdoc}
  */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['aggregator_feed'][$k] = $row;
    }
    parent::setUp();
  }

}
