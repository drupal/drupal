<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterInOperatorTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\filter\InOperator handler.
 */
class FilterInOperatorTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Filter: In-operator',
      'description' => 'Test the core Drupal\views\Plugin\views\filter\InOperator handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['filter']['id'] = 'in_operator';

    return $data;
  }

  public function testFilterInOperatorSimple() {
    $view = $this->getView();

    // Add a in_operator ordering.
    $view->displayHandlers['default']->overrideOption('filters', array(
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
    $this->assertIdenticalResultset($view, $expected_result, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    $view = $this->getView();

    // Add a in_operator ordering.
    $view->displayHandlers['default']->overrideOption('filters', array(
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
    $this->assertIdenticalResultset($view, $expected_result, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  public function testFilterInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers['page_1']->overrideOption('filters', $filters);

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
    $this->assertIdenticalResultset($view, $expected_result, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  public function testFilterNotInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers['page_1']->overrideOption('filters', $filters);

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
    $this->assertIdenticalResultset($view, $expected_result, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
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
