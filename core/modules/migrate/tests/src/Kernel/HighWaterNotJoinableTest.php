<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

/**
 * Tests the high water handling.
 *
 * @covers \Drupal\migrate_high_water_test\Plugin\migrate\source\HighWaterTest
 * @group migrate
 */
class HighWaterNotJoinableTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_drupal',
    'migrate_high_water_test',
  ];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // Test high water when the map is not joinable.
    // The source data.
    $tests[0]['source_data']['high_water_node'] = [
      [
        'id' => 1,
        'title' => 'Item 1',
        'changed' => 1,
      ],
      [
        'id' => 2,
        'title' => 'Item 2',
        'changed' => 2,
      ],
      [
        'id' => 3,
        'title' => 'Item 3',
        'changed' => 3,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => 2,
        'title' => 'Item 2',
        'changed' => 2,
      ],
      [
        'id' => 3,
        'title' => 'Item 3',
        'changed' => 3,
      ],
    ];

    // The expected count is the count returned by the query before the query
    // is modified by SqlBase::initializeIterator().
    $tests[0]['expected_count'] = 3;
    $tests[0]['configuration'] = [
      'high_water_property' => [
        'name' => 'changed',
      ],
    ];
    $tests[0]['high_water'] = $tests[0]['source_data']['high_water_node'][0]['changed'];

    // Test high water initialized to NULL.
    $tests[1]['source_data'] = $tests[0]['source_data'];
    $tests[1]['expected_data'] = [
      [
        'id' => 1,
        'title' => 'Item 1',
        'changed' => 1,
      ],
      [
        'id' => 2,
        'title' => 'Item 2',
        'changed' => 2,
      ],
      [
        'id' => 3,
        'title' => 'Item 3',
        'changed' => 3,
      ],
    ];
    $tests[1]['expected_count'] = $tests[0]['expected_count'];
    $tests[1]['configuration'] = $tests[0]['configuration'];
    $tests[1]['high_water'] = NULL;

    // Test high water initialized to an empty string.
    $tests[2]['source_data'] = $tests[0]['source_data'];
    $tests[2]['expected_data'] = [
      [
        'id' => 1,
        'title' => 'Item 1',
        'changed' => 1,
      ],
      [
        'id' => 2,
        'title' => 'Item 2',
        'changed' => 2,
      ],
      [
        'id' => 3,
        'title' => 'Item 3',
        'changed' => 3,
      ],
    ];
    $tests[2]['expected_count'] = $tests[0]['expected_count'];
    $tests[2]['configuration'] = $tests[0]['configuration'];
    $tests[2]['high_water'] = '';

    return $tests;
  }

}
