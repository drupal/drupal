<?php
/**
 * @file
 * Definition of Drupal\views\Tests\ViewsTestBase
 */

namespace Drupal\views\Tests;
use Drupal\simpletest\WebTestBase;
use Drupal\views\View;

/**
   * Abstract class for views testing.
   */
abstract class ViewsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'views_ui');

  protected function setUp() {
    parent::setUp();

    // @todo Remove this hack or move it to child classes.
    views_init();
    views_module_include('views_default', TRUE);
    views_get_all_views(TRUE);
    menu_router_rebuild();
  }

  /**
   * Helper function: verify a result set returned by view.
   *
   * The comparison is done on the string representation of the columns of the
   * column map, taking the order of the rows into account, but not the order
   * of the columns.
   *
   * @param $view
   *  An executed View.
   * @param $expected_result
   *  An expected result set.
   * @param $column_map
   *  An associative array mapping the columns of the result set from the view
   *  (as keys) and the expected result set (as values).
   */
  protected function assertIdenticalResultset($view, $expected_result, $column_map = array(), $message = 'Identical result set') {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, $message, 'assertIdentical');
  }

  /**
   * Helper function: verify a result set returned by view..
   *
   * Inverse of ViewsTestCase::assertIdenticalResultset().
   *
   * @param $view
   *  An executed View.
   * @param $expected_result
   *  An expected result set.
   * @param $column_map
   *  An associative array mapping the columns of the result set from the view
   *  (as keys) and the expected result set (as values).
   */
  protected function assertNotIdenticalResultset($view, $expected_result, $column_map = array(), $message = 'Identical result set') {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, $message, 'assertNotIdentical');
  }

  protected function assertIdenticalResultsetHelper($view, $expected_result, $column_map, $message, $assert_method) {
    // Convert $view->result to an array of arrays.
    $result = array();
    foreach ($view->result as $key => $value) {
      $row = array();
      foreach ($column_map as $view_column => $expected_column) {
        // The comparison will be done on the string representation of the value.
        $row[$expected_column] = (string) $value->$view_column;
      }
      $result[$key] = $row;
    }

    // Remove the columns we don't need from the expected result.
    foreach ($expected_result as $key => $value) {
      $row = array();
      foreach ($column_map as $expected_column) {
        // The comparison will be done on the string representation of the value.
        $row[$expected_column] = (string) (is_object($value) ? $value->$expected_column : $value[$expected_column]);
      }
      $expected_result[$key] = $row;
    }

    // Reset the numbering of the arrays.
    $result = array_values($result);
    $expected_result = array_values($expected_result);

    $this->verbose('<pre>Returned data set: ' . print_r($result, TRUE) . "\n\nExpected: ". print_r($expected_result, TRUE));

    // Do the actual comparison.
    return $this->$assert_method($result, $expected_result, $message);
  }

  /**
   * Helper function: order an array of array based on a column.
   */
  protected function orderResultSet($result_set, $column, $reverse = FALSE) {
    $this->sort_column = $column;
    $this->sort_order = $reverse ? -1 : 1;
    usort($result_set, array($this, 'helperCompareFunction'));
    return $result_set;
  }

  protected $sort_column = NULL;
  protected $sort_order = 1;

  /**
   * Helper comparison function for orderResultSet().
   */
  protected function helperCompareFunction($a, $b) {
    $value1 = $a[$this->sort_column];
    $value2 = $b[$this->sort_column];
    if ($value1 == $value2) {
      return 0;
    }
    return $this->sort_order * (($value1 < $value2) ? -1 : 1);
  }

  /**
   * Helper function to check whether a button with a certain id exists and has a certain label.
   */
  protected function helperButtonHasLabel($id, $expected_label, $message = 'Label has the expected value: %label.') {
    return $this->assertFieldById($id, $expected_label, t($message, array('%label' => $expected_label)));
  }

  /**
   * Helper function to execute a view with debugging.
   *
   * @param view $view
   * @param array $args
   */
  protected function executeView($view, $args = array()) {
    $view->set_display();
    $view->pre_execute($args);
    $view->execute();
    $this->verbose('<pre>Executed view: ' . ((string) $view->build_info['query']) . '</pre>');
  }
}
