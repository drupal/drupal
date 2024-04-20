<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore daycount totalcount

/**
 * Tests the node_counter source plugin.
 *
 * @covers \Drupal\statistics\Plugin\migrate\source\NodeCounter
 *
 * @group statistics
 * @group legacy
 */
class NodeCounterTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal', 'statistics'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node_counter'] = [
      [
        'nid' => 1,
        'totalcount' => 2,
        'daycount' => 0,
        'timestamp' => 1421727536,
      ],
      [
        'nid' => 2,
        'totalcount' => 1,
        'daycount' => 0,
        'timestamp' => 1471428059,
      ],
      [
        'nid' => 3,
        'totalcount' => 1,
        'daycount' => 0,
        'timestamp' => 1471428153,
      ],
      [
        'nid' => 4,
        'totalcount' => 1,
        'daycount' => 1,
        'timestamp' => 1478755275,
      ],
      [
        'nid' => 5,
        'totalcount' => 1,
        'daycount' => 1,
        'timestamp' => 1478755314,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['node_counter'];

    return $tests;
  }

}
