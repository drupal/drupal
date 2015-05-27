<?php

/**
 * @file
 * Contains \Drupal\datetime\Tests\Views\FilterDateTimeTest.
 */

namespace Drupal\datetime\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the Drupal\datetime\Plugin\views\filter\Date handler.
 *
 * @group datetime
 */
class FilterDateTimeTest extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = array('test_filter_datetime');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add some basic test nodes.
    $dates = array(
      '2000-10-10',
      '2001-10-10',
      '2002-10-10',
      \Drupal::service('date.formatter')->format(REQUEST_TIME + 86400, 'custom', 'Y-m-d'),
    );
    foreach ($dates as $date) {
      $this->nodes[] = $this->drupalCreateNode(array(
        'field_date' => array(
          'value' => $date,
        )
      ));
    }
  }

  /**
   * Test filter operations.
   */
  public function testDatetimeFilter() {
    $this->_testOffset();
    $this->_testBetween();
  }

  /**
   * Test offset operations.
   */
  protected function _testOffset() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    // Test simple operations.
    $view->initHandlers();

    $view->filter[$field]->operator = '>';
    $view->filter[$field]->value['type'] = 'offset';
    $view->filter[$field]->value['value'] = '+1 hour';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test offset for between operator.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['type'] = 'offset';
    $view->filter[$field]->value['max'] = '+2 days';
    $view->filter[$field]->value['min'] = '+1 hour';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

  /**
   *  Test between operations.
   */
  protected function _testBetween() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    // Test between with min and max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['min'] = '2001-01-01';
    $view->filter[$field]->value['max'] = '2002-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[1]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test between with just max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['max'] = '2002-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[0]->id()),
      array('nid' => $this->nodes[1]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with min and max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'not between';
    $view->filter[$field]->value['min'] = '2001-01-01';
    $view->filter[$field]->value['max'] = '2002-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[0]->id()),
      array('nid' => $this->nodes[2]->id()),
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with just max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'not between';
    $view->filter[$field]->value['max'] = '2001-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = array(
      array('nid' => $this->nodes[1]->id()),
      array('nid' => $this->nodes[2]->id()),
      array('nid' => $this->nodes[3]->id()),
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

}
