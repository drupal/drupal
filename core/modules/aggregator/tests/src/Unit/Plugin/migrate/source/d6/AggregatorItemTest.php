<?php

/**
 * @file
 * Contains \Drupal\Tests\aggregator\Unit\Plugin\migrate\source\d6\AggregatorItemTest.
 */

namespace Drupal\Tests\aggregator\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 aggregator item source plugin.
 *
 * @group aggregator
 */
class AggregatorItemTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\aggregator\Plugin\migrate\source\d6\AggregatorItem';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
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

}
