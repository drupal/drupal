<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\BoxTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 block boxes source plugin.
 *
 * @group migrate_drupal
 */
class BoxTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Box';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_boxes',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  protected $expectedResults = array(
    array(
      'bid' => 1,
      'body' => '<p>I made some custom content.</p>',
      'info' => 'Static Block',
      'format' => 1,
    ),
    array(
      'bid' => 2,
      'body' => '<p>I made some more custom content.</p>',
      'info' => 'Test Content',
      'format' => 1,
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['boxes'] = $this->expectedResults;
    parent::setUp();
  }

}
