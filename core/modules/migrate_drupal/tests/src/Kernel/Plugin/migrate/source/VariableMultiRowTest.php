<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the variable multirow source plugin.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow
 *
 * @group migrate_drupal
 */
class VariableMultiRowTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal'];

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
        'name' => 'foo',
        'value' => 1,
      ],
      [
        'name' => 'bar',
        'value' => FALSE,
      ],
    ];

    // The expected count.
    $tests[0]['expected_count'] = NULL;

    // The source plugin configuration.
    $tests[0]['configuration']['variables'] = [
      'foo',
      'bar',
    ];

    return $tests;
  }

}
