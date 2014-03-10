<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterInOperatorTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\InOperator handler.
 */
class FilterInOperatorTest extends ViewUnitTestBase {

  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected $column_map = array(
    'views_test_data_name' => 'name',
    'views_test_data_age' => 'age',
  );

  public static function getInfo() {
    return array(
      'name' => 'Filter: In-operator',
      'description' => 'Test the core Drupal\views\Plugin\views\filter\InOperator handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', array('key_value_expire'));
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['filter']['id'] = 'in_operator';
    return $data;
  }

  public function testFilterInOperatorSimple() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a in_operator ordering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test_data',
        'value' => array(26, 30),
        'operator' => 'in',
      ),
    ));

    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);

    $view->destroy();
    $view->setDisplay();

    // Add a in_operator ordering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test_data',
        'value' => array(26, 30),
        'operator' => 'not in',
      ),
    ));

    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
  }

  public function testFilterInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 1;
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
  }

  public function testFilterNotInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 2;
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->column_map);
  }

  protected function getGroupedExposedFilters() {
    $filters = array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => array(
          'operator' => 'age_op',
          'label' => 'age',
          'identifier' => 'age',
        ),
        'is_grouped' => TRUE,
        'group_info' => array(
          'label' => 'age',
          'identifier' => 'age',
          'default_group' => 'All',
          'group_items' => array(
            1 => array(
              'title' => 'Age is one of 26, 30',
              'operator' => 'in',
              'value' => array(26, 30),
            ),
            2 => array(
              'title' => 'Age is not one of 26, 30',
              'operator' => 'not in',
              'value' => array(26, 30),
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}
