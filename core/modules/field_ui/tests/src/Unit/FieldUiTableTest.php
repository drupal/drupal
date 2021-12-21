<?php

namespace Drupal\Tests\field_ui\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field_ui\Element\FieldUiTable
 *
 * @group field_ui
 */
class FieldUiTableTest extends UnitTestCase {

  /**
   * @covers ::reduceOrder
   *
   * @dataProvider providerTestReduceOrder
   */
  public function testReduceOrder($array, $expected) {
    $this->assertSame($expected, array_reduce($array, ['Drupal\field_ui\Element\FieldUiTable', 'reduceOrder']));
  }

  /**
   * Provides test data for testReduceOrder().
   */
  public function providerTestReduceOrder() {
    return [
      'Flat' => [
        'array' => [
          [
            'name' => 'foo',
          ],
          [
            'name' => 'bar',
          ],
          [
            'name' => 'baz',
          ],
        ],
        'expected' => ['foo', 'bar', 'baz'],
      ],
      'Nested' => [
        'array' => [
          [
            'name' => 'foo',
            'children' => [
              [
                'name' => 'bar',
                'weight' => 0,
              ],
              [
                'name' => 'baz',
                'weight' => -1,
              ],
            ],
          ],
          [
            'name' => 'biz',
          ],
        ],
        'expected' => ['foo', 'baz', 'bar', 'biz'],
      ],
      'Nested no name key' => [
        'array' => [
          [
            'children' => [
              [
                'name' => 'foo',
                'weight' => -1,
              ],
              [
                'name' => 'bar',
                'weight' => 1,
              ],
              [
                'name' => 'baz',
                'weight' => 0,
              ],
            ],
          ],
          [
            'name' => 'biz',
          ],
        ],
        'expected' => ['foo', 'baz', 'bar', 'biz'],
      ],
    ];
  }

}
