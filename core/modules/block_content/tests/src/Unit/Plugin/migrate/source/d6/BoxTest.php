<?php

/**
 * @file
 * Contains \Drupal\Tests\block_content\Unit\Plugin\migrate\source\d6\BoxTest.
 */

namespace Drupal\Tests\block_content\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 block boxes source plugin.
 *
 * @group block_content
 */
class BoxTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\block_content\Plugin\migrate\source\d6\Box';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_boxes',
    ),
  );

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
