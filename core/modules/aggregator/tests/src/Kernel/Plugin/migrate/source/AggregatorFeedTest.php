<?php

namespace Drupal\Tests\aggregator\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 aggregator feed source plugin.
 *
 * @covers \Drupal\aggregator\Plugin\migrate\source\AggregatorFeed
 * @group aggregator
 */
class AggregatorFeedTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['database']['aggregator_feed'] = [
      [
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
      ],
      [
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
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_results'] = $tests[0]['database']['aggregator_feed'];

    return $tests;
  }

}
