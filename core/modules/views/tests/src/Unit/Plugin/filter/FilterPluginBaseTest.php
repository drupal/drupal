<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\filter;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\filter\FilterPluginBase
 * @group views
 */
class FilterPluginBaseTest extends UnitTestCase {

  /**
   * @covers ::acceptExposedInput
   *
   * @dataProvider acceptExposedInputProvider
   */
  public function testAcceptExposedInput(bool $expected_result, array $options, array $input): void {
    $definition = [
      'title' => 'Accept exposed input Test',
      'group' => 'Test',
    ];
    $filter = new FilterPluginBaseStub([], 'stub', $definition);
    $filter->options = $options;
    $this->assertSame($expected_result, $filter->acceptExposedInput($input));
  }

  /**
   * The data provider for testAcceptExposedInput.
   *
   * @return array
   *   The data set.
   */
  public static function acceptExposedInputProvider() {
    return [
      'not-exposed' => [TRUE, ['exposed' => FALSE], []],
      'exposed-no-input' => [TRUE, ['exposed' => TRUE], []],
      'exposed-zero-input' => [FALSE, [
        'exposed' => TRUE,
        'is_grouped' => FALSE,
        'expose' => [
          'use_operator' => TRUE,
          'operator_id' => '=',
          'identifier' => 'identifier',
        ],
      ], ['identifier' => 0],
      ],
      'exposed-empty-array-input' => [FALSE, [
        'exposed' => TRUE,
        'is_grouped' => FALSE,
        'expose' => [
          'use_operator' => TRUE,
          'operator_id' => '=',
          'identifier' => 'identifier',
        ],
      ], ['identifier' => []],
      ],
    ];
  }

}

/**
 * Empty class to support testing filter plugins.
 */
class FilterPluginBaseStub extends FilterPluginBase {}
