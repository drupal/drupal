<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\QueryTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views_test_data\Plugin\views\query\QueryTest as QueryTestPlugin;

/**
 * Tests query plugins.
 *
 * @group views
 */
class QueryTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['table']['base']['query_id'] = 'query_test';

    return $data;
  }

  /**
   * Tests query plugins.
   */
  public function testQuery() {
    $this->_testInitQuery();
    $this->_testQueryExecute();
    $this->queryMethodsTests();
  }

  /**
   * Tests the ViewExecutable::initQuery method.
   */
  public function _testInitQuery() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->initQuery();
    $this->assertTrue($view->query instanceof QueryTestPlugin, 'Make sure the right query plugin got instantiated.');
  }

  public function _testQueryExecute() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->initQuery();
    $view->query->setAllItems($this->dataSet());

    $this->executeView($view);
    $this->assertTrue($view->result, 'Make sure the view result got filled');
  }

  /**
   * Test methods provided by the QueryPluginBase.
   *
   * @see \Drupal\views\Plugin\views\query\QueryPluginBase
   */
  protected function queryMethodsTests() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->initQuery();
    $this->assertFalse($view->query->getLimit(), 'Default to an empty limit.');
    $rand_number = rand(5, 10);
    $view->query->setLimit($rand_number);
    $this->assertEqual($view->query->getLimit(), $rand_number, 'set_limit adapts the amount of items.');
  }

}
