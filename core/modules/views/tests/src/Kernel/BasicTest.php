<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\views\Views;

/**
 * A basic query test for Views.
 *
 * @group views
 */
class BasicTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_simple_argument'];

  /**
   * Tests a trivial result set.
   */
  public function testSimpleResultSet(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertCount(5, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->dataSet(), [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);
  }

  /**
   * Tests filtering of the result set.
   */
  public function testSimpleFiltering(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a filter.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'operator' => '<',
        'value' => [
          'value' => '28',
          'min' => '',
          'max' => '',
        ],
        'group' => '0',
        'exposed' => FALSE,
        'expose' => [
          'operator' => FALSE,
          'label' => '',
        ],
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    // Build the expected result.
    $dataset = [
      [
        'id' => 1,
        'name' => 'John',
        'age' => 25,
      ],
      [
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ],
      [
        'id' => 4,
        'name' => 'Paul',
        'age' => 26,
      ],
    ];

    // Verify the result.
    $this->assertCount(3, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);
  }

  /**
   * Tests simple argument.
   */
  public function testSimpleArgument(): void {
    // Execute with a view
    $view = Views::getView('test_simple_argument');
    $view->setArguments([27]);
    $this->executeView($view);

    // Build the expected result.
    $dataset = [
      [
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ],
    ];

    // Verify the result.
    $this->assertCount(1, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);

    // Test "show all" if no argument is present.
    $view = Views::getView('test_simple_argument');
    $this->executeView($view);

    // Build the expected result.
    $dataset = $this->dataSet();

    $this->assertCount(5, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);
  }

}
