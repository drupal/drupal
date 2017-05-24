<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Numeric handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\Numeric
 */
class FieldNumericTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests the Numeric handler with different settings.
   *
   * @dataProvider providerTestFieldNumeric
   */
  public function testFieldNumeric($field_settings, $values, $expected_values) {
    $view = Views::getView('test_view');
    $view->setDisplay();

    if (!empty($field_settings)) {
      $view->displayHandlers->get('default')->overrideOption('fields', ['age' => $field_settings]);
    }
    $this->executeView($view);

    foreach ($values as $key => $value) {
      $view->result[0]->views_test_data_age = $value;
      $this->assertSame($expected_values[$key], $view->field['age']->advancedRender($view->result[0]));
    }
  }

  /**
   * Data provider for testFieldNumeric.
   *
   * @return array
   *   The data set containing field settings, values to set and expected
   *   values.
   */
  public function providerTestFieldNumeric() {
    return [
      'no-formating' => [
        [],
        [0, 0.1234, -0.1234, 1000.1234, -1000.1234],
        ['0', '0.1234', '-0.1234', '1,000.1234', '-1,000.1234'],
      ],
      'precision_2-hide_empty-hide_zero' => [
        [
          'hide_empty' => TRUE,
          'precision' => 2,
          'set_precision' => TRUE,
          'empty_zero' => TRUE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
        ],
        [0, 0.1234, -0.1234, 1000.1234, -1000.1234, 0.0001, -0.0001, NULL, ''],
        ['', '0.12', '-0.12', '1,000.12', '-1,000.12', '', '', '', ''],
      ],
      'decimal-separator' => [
        [
          'hide_empty' => TRUE,
          'decimal' => ',',
          'separator' => '.',
          'empty_zero' => TRUE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
        ],
        [0.1234, -0.1234, 1000.1234, -1000.1234],
        ['0,1234', '-0,1234', '1.000,1234', '-1.000,1234'],
      ],
      'precision_2-no_separator' => [
        [
          'hide_empty' => TRUE,
          'precision' => 2,
          'set_precision' => TRUE,
          'decimal' => ',',
          'separator' => '',
          'empty_zero' => TRUE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
        ],
        [1234, 1234.01, -1234, -1234.01],
        ['1234,00', '1234,01', '-1234,00', '-1234,01'],
      ],
      'precision_0-no_separator' => [
        [
          'hide_empty' => TRUE,
          'precision' => 0,
          'set_precision' => TRUE,
          'separator' => '',
          'empty_zero' => TRUE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
        ],
        [1234, 1234.01, -1234, -1234.01],
        ['1234', '1234', '-1234', '-1234'],
      ],
      'precision_0-hide_empty-zero_empty' => [
        [
          'hide_empty' => TRUE,
          'precision' => 0,
          'set_precision' => TRUE,
          'empty_zero' => TRUE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
          'prefix' => 'test: ',
        ],
        [0, 0.1234, -0.1234, 1000.1234, -1000.1234],
        ['', '', '', 'test: 1,000', 'test: -1,000'],
      ],
      'precision_0-hide_empty-not_zero_empty' => [
        [
          'hide_empty' => TRUE,
          'precision' => 0,
          'set_precision' => TRUE,
          'empty_zero' => FALSE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
          'prefix' => '',
        ],
        [0, 0.1234, -0.1234],
        ['0', '0', '0'],
      ],
      'precision_2-hide_empty-not_zero_empty' => [
        [
          'hide_empty' => TRUE,
          'precision' => 2,
          'set_precision' => TRUE,
          'empty_zero' => FALSE,
          'id' => 'age',
          'table' => 'views_test_data',
          'field' => 'age',
          'relationship' => 'none',
          'prefix' => '',
        ],
        [0, 0.001234, -0.001234, NULL],
        ['0.00', '0.00', '0.00', ''],
      ],
    ];
  }

}
