<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterInOperatorTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewsSqlTest;

/**
 * Tests the core Drupal\views\Plugin\views\filter\InOperator handler.
 */
class FilterInOperatorTest extends ViewsSqlTest {
  public static function getInfo() {
    return array(
      'name' => 'Filter: in_operator',
      'description' => 'Test the core Drupal\views\Plugin\views\filter\InOperator handler.',
      'group' => 'Views Handlers',
    );
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test']['age']['filter']['id'] = 'in_operator';

    return $data;
  }

  public function testFilterInOperatorSimple() {
    $view = $this->getBasicView();

    // Add a in_operator ordering.
    $view->display['default']->handler->override_option('filters', array(
      'age' => array(
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test',
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
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));

    $view->delete();
    $view = $this->getBasicView();

    // Add a in_operator ordering.
    $view->display['default']->handler->override_option('filters', array(
      'age' => array(
        'id' => 'age',
        'field' => 'age',
        'table' => 'views_test',
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
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));
  }

  public function testFilterInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 1;
    $view->set_display('page_1');
    $view->display['page_1']->handler->override_option('filters', $filters);

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
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));
  }

  public function testFilterNotInOperatorGroupedExposedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Age, Operator: in, Value: 26, 30
    $filters['age']['group_info']['default_group'] = 2;
    $view->set_display('page_1');
    $view->display['page_1']->handler->override_option('filters', $filters);

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
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));
  }

  protected function getGroupedExposedFilters() {
    $filters = array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test',
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
