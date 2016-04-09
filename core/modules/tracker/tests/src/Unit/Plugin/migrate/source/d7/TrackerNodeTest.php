<?php

namespace Drupal\Tests\tracker\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 tracker node source plugin.
 *
 * @group tracker
 */
class TrackerNodeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\tracker\Plugin\migrate\source\d7\TrackerNode';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_tracker_node',
    ],
  ];

  protected $expectedResults = [
    [
      'nid' => '2',
      'published' => '1',
      'changed' => '1421727536',
    ]
  ];

  /**
  * {@inheritdoc}
  */
  protected function setUp() {
    $this->databaseContents['tracker_node'] = $this->expectedResults;
    parent::setUp();
  }

}
