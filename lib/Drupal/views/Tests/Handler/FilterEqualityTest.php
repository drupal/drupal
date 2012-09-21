<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterEqualityTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\filter\Equality handler.
 */
class FilterEqualityTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Filter: Equality',
      'description' => 'Test the core Drupal\views\Plugin\views\filter\Equality handler.',
      'group' => 'Views Handlers',
    );
  }

  function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->column_map = array(
      'views_test_data_name' => 'name',
    );
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'equality';

    return $data;
  }

  function testEqual() {
    $view = $this->getView();

    // Change the filtering
    $view->displayHandlers['default']->overrideOption('filters', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => array('value' => 'Ringo'),
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  public function testEqualGroupedExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: =, Value: Ringo
    $filters['name']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers['page_1']->overrideOption('filters', $filters);

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  function testNotEqual() {
    $view = $this->getView();

    // Change the filtering
    $view->displayHandlers['default']->overrideOption('filters', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '!=',
        'value' => array('value' => 'Ringo'),
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
      ),
      array(
        'name' => 'George',
      ),
      array(
        'name' => 'Paul',
      ),
      array(
        'name' => 'Meredith',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  public function testEqualGroupedNotExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: !=, Value: Ringo
    $filters['name']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers['page_1']->overrideOption('filters', $filters);

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
      ),
      array(
        'name' => 'George',
      ),
      array(
        'name' => 'Paul',
      ),
      array(
        'name' => 'Meredith',
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }


  protected function getGroupedExposedFilters() {
    $filters = array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'group' => 1,
        'exposed' => TRUE,
        'expose' => array(
          'operator' => 'name_op',
          'label' => 'name',
          'identifier' => 'name',
        ),
        'is_grouped' => TRUE,
        'group_info' => array(
          'label' => 'name',
          'identifier' => 'name',
          'default_group' => 'All',
          'group_items' => array(
            1 => array(
              'title' => 'Name is equal to Ringo',
              'operator' => '=',
              'value' => array('value' => 'Ringo'),
            ),
            2 => array(
              'title' => 'Name is not equal to Ringo',
              'operator' => '!=',
              'value' => array('value' => 'Ringo'),
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}
