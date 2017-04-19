<?php

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
  public static $modules = ['migrate', 'migrate_drupal', 'migrate_high_water_test'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {

    $tests = [];

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
    return $tests;
  }

}
