<?php

namespace Drupal\views\Tests\Handler;

use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\Date handler.
 *
 * @group views
 */
class FilterDateTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_date_between');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'views_ui');

  protected function setUp() {
    parent::setUp();
    // Add some basic test nodes.
    $this->nodes = array();
    $this->nodes[] = $this->drupalCreateNode(array('created' => 100000));
    $this->nodes[] = $this->drupalCreateNode(array('created' => 200000));
    $this->nodes[] = $this->drupalCreateNode(array('created' => 300000));
    $this->nodes[] = $this->drupalCreateNode(array('created' => time() + 86400));

    $this->map = array(
      'nid' => 'nid',
    );
  }

  /**
   * Runs other test methods.
   */
  public function testDateFilter() {
    $this->_testOffset();
    $this->_testBetween();
    $this->_testUiValidation();
  }

  /**
   * Test the general offset functionality.
   */
  protected function _testOffset() {
    $view = Views::getView('test_filter_date_between');

    // Test offset for simple operator.
    $view->initHandlers();
    $view->filter['created']->operator = '>';
    $view->filter['created']->value['type'] = 'offset';
    $view->filter['created']->value['value'] = '+1 hour';
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test offset for between operator.
    $view->initHandlers();
    $view->filter['created']->operator = 'between';
    $view->filter['created']->value['type'] = 'offset';
    $view->filter['created']->value['max'] = '+2 days';
    $view->filter['created']->value['min'] = '+1 hour';
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

  /**
   * Tests the filter operator between/not between.
   */
  protected function _testBetween() {
    $view = Views::getView('test_filter_date_between');

    // Test between with min and max.
    $view->initHandlers();
    $view->filter['created']->operator = 'between';
    $view->filter['created']->value['min'] = format_date(150000, 'custom', 'Y-m-d H:s');
    $view->filter['created']->value['max'] = format_date(250000, 'custom', 'Y-m-d H:s');
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[1]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test between with just max.
    $view->initHandlers();
    $view->filter['created']->operator = 'between';
    $view->filter['created']->value['max'] = format_date(250000, 'custom', 'Y-m-d H:s');
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[0]->id()),
      array('nid' => $this->nodes[1]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with min and max.
    $view->initHandlers();
    $view->filter['created']->operator = 'not between';
    $view->filter['created']->value['min'] = format_date(150000, 'custom', 'Y-m-d H:s');
    $view->filter['created']->value['max'] = format_date(250000, 'custom', 'Y-m-d H:s');
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[0]->id()),
      array('nid' => $this->nodes[2]->id()),
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with just max.
    $view->initHandlers();
    $view->filter['created']->operator = 'not between';
    $view->filter['created']->value['max'] = format_date(150000, 'custom', 'Y-m-d H:s');
    $view->executeDisplay('default');
    $expected_result = array(
      array('nid' => $this->nodes[1]->id()),
      array('nid' => $this->nodes[2]->id()),
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

  /**
   * Make sure the validation callbacks works.
   */
  protected function _testUiValidation() {

    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'administer site configuration')));

    $this->drupalGet('admin/structure/views/view/test_filter_date_between/edit');
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_date_between/default/filter/created');

    $edit = array();
    // Generate a definitive wrong value, which should be checked by validation.
    $edit['options[value][value]'] = $this->randomString() . '-------';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText(t('Invalid date format.'), 'Make sure that validation is run and the invalidate date format is identified.');
  }

}
