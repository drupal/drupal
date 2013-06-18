<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FilterBooleanOperatorTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;

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

    $this->installSchema('system', array('menu_router', 'variable', 'key_value_expire'));
  }

  /**
   * Tests the BooleanOperator filter.
   */
  public function testFilterBooleanOperator() {
    $view = views_get_view('test_view');
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

}

