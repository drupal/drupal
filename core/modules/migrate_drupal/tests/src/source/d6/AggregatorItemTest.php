<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\AggregatorItemTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 aggregator item source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class AggregatorItemTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\AggregatorItem';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_aggregator_item',
    ),
  );

  protected $expectedResults = array(
    array(
      'iid' => 1,
      'fid' => 1,
      'title' => 'This (three) weeks in Drupal Core - January 10th 2014',
      'link' => 'https://groups.drupal.org/node/395218',
      'author' => 'larowlan',
      'description' => "<h2 id='new'>What's new with Drupal 8?</h2>",
      'timestamp' => 1389297196,
      'guid' => '395218 at https://groups.drupal.org',
    ),
  );

  protected $databaseContents = array('aggregator_item' => array(array(
      'iid' => 1,
      'fid' => 1,
      'title' => 'This (three) weeks in Drupal Core - January 10th 2014',
      'link' => 'https://groups.drupal.org/node/395218',
      'author' => 'larowlan',
      'description' => "<h2 id='new'>What's new with Drupal 8?</h2>",
      'timestamp' => 1389297196,
      'guid' => '395218 at https://groups.drupal.org',
    ),
  ));

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 aggregator item source functionality',
      'description' => 'Tests D6 aggregator item source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\AggregatorItem;

class TestAggregatorItem extends AggregatorItem {

  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

}
