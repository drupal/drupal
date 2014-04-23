<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\AggregatorFeedTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 aggregator feed source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class AggregatorFeedTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\AggregatorFeed';

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
  public static function getInfo() {
    return array(
      'name' => 'D6 aggregator feed source functionality',
      'description' => 'Tests D6 aggregator feed source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

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

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\AggregatorFeed;

class TestAggregatorFeed extends AggregatorFeed {

  public function setDatabase(Connection $database) {
    $this->database = $database;
  }

  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
