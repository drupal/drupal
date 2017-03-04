<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests for core Drupal\views\Plugin\views\sort\SortPluginBase handler.
 *
 * @group views
 */
class SortTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests numeric ordering of the result set.
   */
  public function testNumericOrdering() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the ordering
    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'age' => [
        'order' => 'ASC',
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age'), [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);

    $view->destroy();
    $view->setDisplay();

    // Reverse the ordering
    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'age' => [
        'order' => 'DESC',
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age', TRUE), [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);
  }

  /**
   * Tests string ordering of the result set.
   */
  public function testStringOrdering() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the ordering
    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'name' => [
        'order' => 'ASC',
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name'), [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);

    $view->destroy();
    $view->setDisplay();

    // Reverse the ordering
    $view->displayHandlers->get('default')->overrideOption('sorts', [
      'name' => [
        'order' => 'DESC',
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name', TRUE), [
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ]);
  }

}
