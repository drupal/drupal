<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 filter format source plugin.
 *
 * @covers \Drupal\filter\Plugin\migrate\source\d6\FilterFormat
 *
 * @group filter
 */
class FilterFormatTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['filter_formats'] = [
      [
        'format' => 1,
        'name' => 'Filtered HTML',
        'roles' => ',1,2,',
        'cache' => 1,
      ],
      [
        'format' => 2,
        'name' => 'Full HTML',
        'roles' => '',
        'cache' => 1,
      ],
      [
        'format' => 4,
        'name' => 'Example Custom Format',
        'roles' => '4',
        'cache' => 1,
      ],
    ];
    $tests[0]['source_data']['filters'] = [
      [
        'fid' => 1,
        'format' => 1,
        'module' => 'filter',
        'delta' => 2,
        'weight' => 0,
      ],
      [
        'fid' => 2,
        'format' => 1,
        'module' => 'filter',
        'delta' => 0,
        'weight' => 1,
      ],
      [
        'fid' => 3,
        'format' => 1,
        'module' => 'filter',
        'delta' => 1,
        'weight' => 2,
      ],
      [
        'fid' => 4,
        'format' => 2,
        'module' => 'filter',
        'delta' => 2,
        'weight' => 0,
      ],
      [
        'fid' => 5,
        'format' => 2,
        'module' => 'filter',
        'delta' => 1,
        'weight' => 1,
      ],
      [
        'fid' => 6,
        'format' => 2,
        'module' => 'filter',
        'delta' => 3,
        'weight' => 10,
      ],
      [
        'fid' => 7,
        'format' => 4,
        'module' => 'markdown',
        'delta' => 1,
        'weight' => 10,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'format' => 1,
        'name' => 'Filtered HTML',
        'roles' => [1, 2],
        'cache' => 1,
        'filters' => [
          [
            'module' => 'filter',
            'delta' => 2,
            'weight' => 0,
            'settings' => [],
          ],
          [
            'module' => 'filter',
            'delta' => 0,
            'weight' => 1,
            'settings' => [],
          ],
          [
            'module' => 'filter',
            'delta' => 1,
            'weight' => 2,
            'settings' => [],
          ],
        ],
      ],
      [
        'format' => 2,
        'name' => 'Full HTML',
        'roles' => [],
        'cache' => 1,
        'filters' => [
          [
            'module' => 'filter',
            'delta' => 2,
            'weight' => 0,
            'settings' => [],
          ],
          [
            'module' => 'filter',
            'delta' => 1,
            'weight' => 1,
            'settings' => [],
          ],
          [
            'module' => 'filter',
            'delta' => 3,
            'weight' => 10,
            'settings' => [],
          ],
        ],
      ],
      [
        'format' => 4,
        'name' => 'Example Custom Format',
        'roles' => [4],
        'cache' => 1,
        'filters' => [
          // This custom format uses a filter defined by a contrib module.
          [
            'module' => 'markdown',
            'delta' => 1,
            'weight' => 10,
            'settings' => [],
          ],
        ],
      ],
    ];

    return $tests;
  }

}
