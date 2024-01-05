<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\DefaultValue;

/**
 * Tests the default_value process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\DefaultValue
 */
class DefaultValueTest extends MigrateProcessTestCase {

  /**
   * Tests the default_value process plugin.
   *
   * @covers ::transform
   *
   * @dataProvider defaultValueDataProvider
   */
  public function testDefaultValue($configuration, $expected_value, $value) {
    $process = new DefaultValue($configuration, 'default_value', []);
    $value = $process->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_value, $value);
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   */
  public function defaultValueDataProvider() {
    return [
      'strict_true_value_populated_array' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => [0, 1, 2],
        'value' => [0, 1, 2],
      ],
      'strict_true_value_empty_string' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => '',
        'value' => '',
      ],
      'strict_true_value_false' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => FALSE,
        'value' => FALSE,
      ],
      'strict_true_value_null' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => NULL,
      ],
      'strict_true_value_zero_string' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => '0',
        'value' => '0',
      ],
      'strict_true_value_zero' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => 0,
        'value' => 0,
      ],
      'strict_true_value_empty_array' => [
        'configuration' => [
          'strict' => TRUE,
          'default_value' => 1,
        ],
        'expected_value' => [],
        'value' => [],
      ],
      'array_populated' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => [0, 1, 2],
        'value' => [0, 1, 2],
      ],
      'empty_string' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => '',
      ],
      'false' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => FALSE,
      ],
      'null' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => NULL,
      ],
      'string_zero' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => '0',
      ],
      'int_zero' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => 0,
      ],
      'empty_array' => [
        'configuration' => [
          'default_value' => 1,
        ],
        'expected_value' => 1,
        'value' => [],
      ],
    ];
  }

}
