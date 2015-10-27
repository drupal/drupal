<?php

/**
 * @file
 * Contains \Drupal\Tests\ban\Unit\Plugin\migrate\source\d7\BlockedIpsTest.
 */

namespace Drupal\Tests\ban\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 blocked_ip source plugin.
 *
 * @coversDefaultClass \Drupal\ban\Plugin\migrate\source\d7\BlockedIps
 * @group ban
 */
class BlockedIpsTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\ban\Plugin\migrate\source\d7\BlockedIps';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_blocked_ips',
    ],
  ];

  protected $expectedResults = [
    [
      'ip' => '127.0.0.1',
    ],
  ];

  /**
  * {@inheritdoc}
  */
  protected function setUp() {
    $this->databaseContents['blocked_ips'] = [
      [
        'iid' => 1,
        'ip' => '127.0.0.1',
      ]
    ];
    parent::setUp();
  }

}
