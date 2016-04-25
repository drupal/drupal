<?php

namespace Drupal\Tests\tracker\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 tracker user source plugin.
 *
 * @group tracker
 */
class TrackerUserTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\tracker\Plugin\migrate\source\d7\TrackerUser';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_tracker_user',
    ],
  ];

  protected $expectedResults = [
    [
      'nid' => '1',
      'uid' => '2',
      'published' => '1',
      'changed' => '1421727536',
    ]
  ];

  /**
  * {@inheritdoc}
  */
  protected function setUp() {
    $this->databaseContents['tracker_user'] = $this->expectedResults;
    parent::setUp();
  }

}
