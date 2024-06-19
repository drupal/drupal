<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\Equality handler.
 *
 * @group views
 */
class FilterEqualityTest extends ViewsKernelTestBase {

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
    'views_test_data_name' => 'name',
  ];

  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'equality';
    return $data;
  }

  public function testEqual(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 'Ringo',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testEqualGroupedExposed(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Name, Operator: =, Value: Ringo
    $filters['name']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testNotEqual(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '!=',
        'value' => 'Ringo',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testEqualGroupedNotExposed(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Name, Operator: !=, Value: Ringo
    $filters['name']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  protected function getGroupedExposedFilters() {
    $filters = [
      'name' => [
        'id' => 'name',
        'plugin_id' => 'equality',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'group' => 1,
        'exposed' => TRUE,
        'expose' => [
          'operator' => 'name_op',
          'label' => 'name',
          'identifier' => 'name',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'name',
          'identifier' => 'name',
          'default_group' => 'All',
          'group_items' => [
            1 => [
              'title' => 'Name is equal to Ringo',
              'operator' => '=',
              'value' => 'Ringo',
            ],
            2 => [
              'title' => 'Name is not equal to Ringo',
              'operator' => '!=',
              'value' => 'Ringo',
            ],
          ],
        ],
      ],
    ];
    return $filters;
  }

}
