<?php

namespace Drupal\Tests\filter\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 filter format source plugin.
 *
 * @covers \Drupal\filter\Plugin\migrate\source\d7\FilterFormat
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
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['filter_format'] = [
      [
        'format' => 'custom_text_format',
        'name' => 'Custom Text format',
        'cache' => 1,
        'status' => 1,
        'weight' => 0,
      ],
      [
        'format' => 'full_html',
        'name' => 'Full HTML',
        'cache' => 1,
        'status' => 1,
        'weight' => 1,
      ],
    ];
    $tests[0]['source_data']['filter'] = [
      [
        'format' => 'custom_text_format',
        'module' => 'filter',
        'name' => 'filter_autop',
        'weight' => 0,
        'status' => 1,
        'settings' => serialize([]),
      ],
      [
        'format' => 'custom_text_format',
        'module' => 'filter',
        'name' => 'filter_html',
        'weight' => 1,
        'status' => 1,
        'settings' => serialize([]),
      ],
      [
        'format' => 'full_html',
        'module' => 'filter',
        'name' => 'filter_url',
        'weight' => 0,
        'status' => 1,
        'settings' => serialize([]),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'format' => 'custom_text_format',
        'name' => 'Custom Text format',
        'cache' => 1,
        'status' => 1,
        'weight' => 0,
        'filters' => [
          'filter_autop' => [
            'format' => 'custom_text_format',
            'module' => 'filter',
            'name' => 'filter_autop',
            'weight' => 0,
            'status' => 1,
            'settings' => [],
          ],
          'filter_html' => [
            'format' => 'custom_text_format',
            'module' => 'filter',
            'name' => 'filter_html',
            'weight' => 1,
            'status' => 1,
            'settings' => [],
          ],
        ],
      ],
      [
        'format' => 'full_html',
        'name' => 'Full HTML',
        'cache' => 1,
        'status' => 1,
        'weight' => 1,
        'filters' => [
          'filter_url' => [
            'format' => 'full_html',
            'module' => 'filter',
            'name' => 'filter_url',
            'weight' => 0,
            'status' => 1,
            'settings' => [],
          ],
        ],
      ],
    ];

    return $tests;
  }

}
