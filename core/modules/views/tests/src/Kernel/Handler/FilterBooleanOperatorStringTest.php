<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests core's BooleanOperatorString views filter handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\BooleanOperatorString
 */
class FilterBooleanOperatorStringTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = [
    'views_test_data_id' => 'id',
  ];

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['status'] = [
      'description' => 'The status of this record',
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $views_data = parent::viewsData();

    $views_data['views_test_data']['status']['filter']['id'] = 'boolean_string';

    return $views_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function dataSet() {
    $data = parent::dataSet();

    foreach ($data as &$row) {
      if ($row['status']) {
        $row['status'] = 'Enabled';
      }
      else {
        $row['status'] = '';
      }
    }

    return $data;
  }

  /**
   * Tests the BooleanOperatorString filter.
   */
  public function testFilterBooleanOperatorString(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'status' => [
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 0,
      ],
    ]);
    $this->executeView($view);

    $expected_result = [
      ['id' => 2],
      ['id' => 4],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();
    $view->setDisplay();

    // Add the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'status' => [
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 1,
      ],
    ]);
    $this->executeView($view);

    $expected_result = [
      ['id' => 1],
      ['id' => 3],
      ['id' => 5],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Tests the Boolean filter with grouped exposed form enabled.
   */
  public function testFilterGroupedExposed(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    $view->setExposedInput(['status' => 1]);
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = [
      ['id' => 1],
      ['id' => 3],
      ['id' => 5],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    $view->setExposedInput(['status' => 2]);
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = [
      ['id' => 2],
      ['id' => 4],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Provides grouped exposed filter configuration.
   *
   * @return array
   *   Returns the filter configuration for exposed filters.
   */
  protected function getGroupedExposedFilters() {
    $filters = [
      'status' => [
        'id' => 'status',
        'table' => 'views_test_data',
        'field' => 'status',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => [
          'operator' => 'status_op',
          'label' => 'status',
          'identifier' => 'status',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'status',
          'identifier' => 'status',
          'default_group' => 'All',
          'group_items' => [
            1 => [
              'title' => 'Active',
              'operator' => '=',
              'value' => '1',
            ],
            2 => [
              'title' => 'Blocked',
              'operator' => '=',
              'value' => '0',
            ],
          ],
        ],
      ],
    ];
    return $filters;
  }

}
