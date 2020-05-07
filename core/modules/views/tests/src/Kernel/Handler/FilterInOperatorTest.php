<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\InOperator handler.
 *
 * @group views
 */
class FilterInOperatorTest extends ViewsKernelTestBase {
  use StringTranslationTrait;

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
    'views_test_data_age' => 'age',
  ];

  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['filter']['id'] = 'in_operator';
    return $data;
  }

  public function testFilterInOperatorSimple() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a in_operator ordering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test_data',
        'value' => [26, 30],
        'operator' => 'in',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'Paul',
        'age' => 26,
      ],
      [
        'name' => 'Meredith',
        'age' => 30,
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();
    $view->setDisplay();

    // Add a in_operator ordering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test_data',
        'value' => [26, 30],
        'operator' => 'not in',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'John',
        'age' => 25,
      ],
      [
        'name' => 'George',
        'age' => 27,
      ],
      [
        'name' => 'Ringo',
        'age' => 28,
      ],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  public function testFilterInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 1;
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'Paul',
        'age' => 26,
      ],
      [
        'name' => 'Meredith',
        'age' => 30,
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  public function testFilterNotInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 2;
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'John',
        'age' => 25,
      ],
      [
        'name' => 'George',
        'age' => 27,
      ],
      [
        'name' => 'Ringo',
        'age' => 28,
      ],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Tests that we can safely change the identifier on a grouped filter.
   */
  public function testFilterGroupedChangedIdentifier() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    $filters['age']['group_info']['default_group'] = 2;
    $filters['age']['group_info']['identifier'] = 'not-age';
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'John',
        'age' => 25,
      ],
      [
        'name' => 'George',
        'age' => 27,
      ],
      [
        'name' => 'Ringo',
        'age' => 28,
      ],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  protected function getGroupedExposedFilters() {
    $filters = [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => [
          'operator' => 'age_op',
          'label' => 'age',
          'identifier' => 'age',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'age',
          'identifier' => 'age',
          'default_group' => 'All',
          'group_items' => [
            1 => [
              'title' => 'Age is one of 26, 30',
              'operator' => 'in',
              'value' => [26, 30],
            ],
            2 => [
              'title' => 'Age is not one of 26, 30',
              'operator' => 'not in',
              'value' => [26, 30],
            ],
          ],
        ],
      ],
    ];
    return $filters;
  }

  /**
   * Tests that the InOperator filter can handle TranslateableMarkup.
   */
  public function testFilterOptionAsMarkup() {
    $view = $this->prophesize(ViewExecutable::class);
    $display = $this->prophesize(DisplayPluginBase::class);
    $display->getOption('relationships')->willReturn(FALSE);
    $view->display_handler = $display->reveal();

    /** @var \Drupal\views\Plugin\ViewsHandlerManager $manager */
    $manager = $this->container->get('plugin.manager.views.filter');
    /** @var \Drupal\views\Plugin\views\filter\InOperator $operator */
    $operator = $manager->createInstance('in_operator');
    $options = ['value' => ['foo' => [], 'baz' => []]];
    $operator->init($view->reveal(), $display->reveal(), $options);

    $input_options = [
      'foo' => 'bar',
      'baz' => $this->t('qux'),
      'quux' => (object) ['option' => ['quux' => 'corge']],
    ];
    $reduced_values = $operator->reduceValueOptions($input_options);

    $this->assertSame(['foo', 'baz'], array_keys($reduced_values));
    $this->assertInstanceOf(TranslatableMarkup::class, $reduced_values['baz']);
    $this->assertSame('qux', (string) $reduced_values['baz']);
    $this->assertSame('bar', $reduced_values['foo']);

  }

}
