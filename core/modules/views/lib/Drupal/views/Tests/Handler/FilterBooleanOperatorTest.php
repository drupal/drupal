<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FilterBooleanOperatorTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the BooleanOperator filter handler.
 *
 * @see \Drupal\views\Plugin\views\filter\BooleanOperator
 */
class FilterBooleanOperatorTest extends ViewUnitTestBase {

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected $column_map = array(
    'views_test_data_id' => 'id',
  );

  public static function getInfo() {
    return array(
      'name' => 'Filter: Boolean operator',
      'description' => 'Test the core Drupal\views\Plugin\views\filter\BooleanOperator handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', array('key_value_expire'));
  }

  /**
   * Tests the BooleanOperator filter.
   */
  public function testFilterBooleanOperator() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'status' => array(
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 0,
      ),
    ));
    $this->executeView($view);

    $expected_result = array(
      array('id' => 2),
      array('id' => 4),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);

    $view->destroy();
    $view->setDisplay();

    // Add the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'status' => array(
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 1,
      ),
    ));
    $this->executeView($view);

    $expected_result = array(
      array('id' => 1),
      array('id' => 3),
      array('id' => 5),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
  }

  /**
   * Tests the boolean filter with grouped exposed form enabled.
   */
  public function testFilterGroupedExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    $view->setExposedInput(array('status' => 1));
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array('id' => 1),
      array('id' => 3),
      array('id' => 5),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
    $view->destroy();

    $view->setExposedInput(array('status' => 2));
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array('id' => 2),
      array('id' => 4),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
  }

  /**
   * Provides grouped exposed filter configuration.
   *
   * @return array
   */
  protected function getGroupedExposedFilters() {
    $filters = array(
      'status' => array(
        'id' => 'status',
        'table' => 'views_test_data',
        'field' => 'status',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => array(
          'operator' => 'status_op',
          'label' => 'status',
          'identifier' => 'status',
        ),
        'is_grouped' => TRUE,
        'group_info' => array(
          'label' => 'status',
          'identifier' => 'status',
          'default_group' => 'All',
          'group_items' => array(
            1 => array(
              'title' => 'Active',
              'operator' => '=',
              'value' => '1',
            ),
            2 => array(
              'title' => 'Blocked',
              'operator' => '=',
              'value' => '0',
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}

