<?php

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
  public static $modules = ['migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
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
    $tests[0]['expected_count'] = NULL;

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

    $tests[1]['expected_count'] = NULL;

    $tests[1]['configuration']['variables'] = [
      'foo',
      'bar0',
    ];

    // Tests requesting mis-spelled variable names.
    $tests[2]['source_data']['variable'] = [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ];
    $tests[2]['expected_data'] = [
      [
        'id' => 'foo0',
      ],
    ];
    $tests[2]['expected_count'] = NULL;
    $tests[2]['configuration']['variables'] = [
      'foo0',
      'bar0',
    ];

    return $tests;
  }

}
