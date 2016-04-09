<?php

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
  public static $testViews = array('test_view', 'test_simple_argument');

  /**
   * Tests a trivial result set.
   */
  public function testSimpleResultSet() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->dataSet(), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  /**
   * Tests filtering of the result set.
   */
  public function testSimpleFiltering() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a filter.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'operator' => '<',
        'value' => array(
          'value' => '28',
          'min' => '',
          'max' => '',
        ),
        'group' => '0',
        'exposed' => FALSE,
        'expose' => array(
          'operator' => FALSE,
          'label' => '',
        ),
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Build the expected result.
    $dataset = array(
      array(
        'id' => 1,
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'id' => 4,
        'name' => 'Paul',
        'age' => 26,
      ),
    );

    // Verify the result.
    $this->assertEqual(3, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  /**
   * Tests simple argument.
   */
  public function testSimpleArgument() {
    // Execute with a view
    $view = Views::getView('test_simple_argument');
    $view->setArguments(array(27));
    $this->executeView($view);

    // Build the expected result.
    $dataset = array(
      array(
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ),
    );

    // Verify the result.
    $this->assertEqual(1, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    // Test "show all" if no argument is present.
    $view = Views::getView('test_simple_argument');
    $this->executeView($view);

    // Build the expected result.
    $dataset = $this->dataSet();

    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

}
