<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the variable source plugin.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\Variable
 *
 * @group migrate_drupal
 */
class VariableTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['variable'] = [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => 'foo',
        'foo' => 1,
        'bar' => FALSE,
      ],
    ];

    // The expected count.
    $tests[0]['expected_count'] = 1;

    // The source plugin configuration.
    $tests[0]['configuration']['variables'] = [
      'foo',
      'bar',
    ];

    // Tests getting one of two variables.
    $tests[1]['source_data']['variable'] = [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ];

    $tests[1]['expected_data'] = [
      [
        'id' => 'foo',
        'foo' => 1,
      ],
    ];

    $tests[1]['expected_count'] = 1;

    $tests[1]['configuration']['variables'] = [
      'foo',
      'bar0',
    ];

    // Tests requesting mis-spelled variable names. If none of the required
    // variables are available, this plugin still returns a single row.
    $tests[2]['source_data']['variable'] = [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ];
    $tests[2]['expected_data'] = [
      [
        'id' => 'foo0',
      ],
    ];
    $tests[2]['expected_count'] = 1;
    $tests[2]['configuration']['variables'] = [
      'foo0',
      'bar0',
    ];

    $source_data = [
      'variable' => [
        ['name' => 'foo', 'value' => 'i:1;'],
        ['name' => 'bar', 'value' => 'b:0;'],
        ['name' => 'baz', 'value' => 's:6:"foobar";'],
      ],
    ];

    // Test cases with only 'variables_no_row_if_missing' configuration.
    $variables_no_row_if_missing_tests = [
      'Two required variables, all of them are available' => [
        'source_data' => $source_data,
        'expected_data' => [
          [
            'id' => 'foo',
            'foo' => 1,
            'bar' => FALSE,
          ],
        ],
        'expected_count' => 1,
        'configuration' => [
          'variables_no_row_if_missing' => [
            'foo',
            'bar',
          ],
        ],
      ],
      'Two required variables, only one is available' => [
        'source_data' => $source_data,
        'expected_data' => [],
        'expected_count' => 0,
        'configuration' => [
          'variables_no_row_if_missing' => [
            'foo',
            'bar0',
          ],
        ],
      ],
      'One required and available variable' => [
        'source_data' => $source_data,
        'expected_data' => [
          [
            'id' => 'baz',
            'baz' => 'foobar',
          ],
        ],
        'expected_count' => 1,
        'configuration' => [
          'variables_no_row_if_missing' => [
            'baz',
          ],
        ],
      ],
      'One required, but missing variable' => [
        'source_data' => $source_data,
        'expected_data' => [],
        'expected_count' => 0,
        'configuration' => [
          'variables_no_row_if_missing' => [
            'bar0',
          ],
        ],
      ],
      // Test cases with both 'variables' and 'variables_no_row_if_missing'
      // configuration.
      'One optional and two required variables, all of them are available' => [
        'source_data' => $source_data,
        'expected_data' => [
          [
            'id' => 'foo',
            'foo' => 1,
            'bar' => FALSE,
            'baz' => 'foobar',
          ],
        ],
        'expected_count' => 1,
        'configuration' => [
          'variables' => ['foo'],
          'variables_no_row_if_missing' => ['bar', 'baz'],
        ],
      ],
      'One optional and two required variables, only one required is available' => [
        'source_data' => $source_data,
        'expected_data' => [],
        'expected_count' => 0,
        'configuration' => [
          'variables' => ['foo'],
          'variables_no_row_if_missing' => ['bar', 'foobar'],
        ],
      ],
      'Two optional and one required and available variable, every optional is missing' => [
        'source_data' => $source_data,
        'expected_data' => [
          [
            'id' => 'qux',
            'bar' => FALSE,
          ],
        ],
        'expected_count' => 1,
        'configuration' => [
          'variables' => ['qux', 'waldo'],
          'variables_no_row_if_missing' => ['bar'],
        ],
      ],
      'Two available optional and a required, but missing variable' => [
        'source_data' => $source_data,
        'expected_data' => [],
        'expected_count' => 0,
        'configuration' => [
          'variables' => ['baz', 'foo'],
          'variables_no_row_if_missing' => [
            'foo_bar_baz',
          ],
        ],
      ],
    ];

    return $tests + $variables_no_row_if_missing_tests;
  }

}
