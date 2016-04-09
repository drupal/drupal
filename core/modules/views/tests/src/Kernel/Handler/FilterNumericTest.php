<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the numeric filter handler.
 *
 * @group views
 */
class FilterNumericTest extends ViewsKernelTestBase {

  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_name' => 'name',
    'views_test_data_age' => 'age',
  );

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['filter']['allow empty'] = TRUE;
    $data['views_test_data']['id']['filter']['allow empty'] = FALSE;

    return $data;
  }

  public function testFilterNumericSimple() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'operator' => '=',
        'value' => array('value' => 28),
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericExposedGroupedSimple() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Age, Operator: =, Value: 28
    $filters['age']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericBetween() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'operator' => 'between',
        'value' => array(
          'min' => 26,
          'max' => 29,
        ),
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);

    // test not between
    $view->destroy();
    $view->setDisplay();

      // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'operator' => 'not between',
        'value' => array(
          'min' => 26,
          'max' => 29,
        ),
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericExposedGroupedBetween() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Age, Operator: between, Value: 26 and 29
    $filters['age']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericExposedGroupedNotBetween() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Age, Operator: between, Value: 26 and 29
    $filters['age']['group_info']['default_group'] = 3;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericEmpty() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'operator' => 'empty',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);

    $view->destroy();
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'operator' => 'not empty',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
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
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericExposedGroupedEmpty() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Age, Operator: empty, Value:
    $filters['age']['group_info']['default_group'] = 4;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterNumericExposedGroupedNotEmpty() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');
    $view->newDisplay('page', 'Page', 'page_1');

    // Filter: Age, Operator: empty, Value:
    $filters['age']['group_info']['default_group'] = 5;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);
    $resultset = array(
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
      array(
        'name' => 'Paul',
        'age' => 26,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testAllowEmpty() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'id' => array(
        'id' => 'id',
        'table' => 'views_test_data',
        'field' => 'id',
        'relationship' => 'none',
      ),
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    $view->initHandlers();

    $id_operators = $view->filter['id']->operators();
    $age_operators = $view->filter['age']->operators();

    $this->assertFalse(isset($id_operators['empty']));
    $this->assertFalse(isset($id_operators['not empty']));
    $this->assertTrue(isset($age_operators['empty']));
    $this->assertTrue(isset($age_operators['not empty']));
  }

  protected function getGroupedExposedFilters() {
    $filters = array(
      'age' => array(
        'id' => 'age',
        'plugin_id' => 'numeric',
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
              'title' => 'Age is 28',
              'operator' => '=',
              'value' => array('value' => 28),
            ),
            2 => array(
              'title' => 'Age is between 26 and 29',
              'operator' => 'between',
              'value' => array(
                'min' => 26,
                'max' => 29,
              ),
            ),
            3 => array(
              'title' => 'Age is not between 26 and 29',
              'operator' => 'not between',
              'value' => array(
                'min' => 26,
                'max' => 29,
              ),
            ),
            4 => array(
              'title' => 'Age is empty',
              'operator' => 'empty',
            ),
            5 => array(
              'title' => 'Age is not empty',
              'operator' => 'not empty',
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}
