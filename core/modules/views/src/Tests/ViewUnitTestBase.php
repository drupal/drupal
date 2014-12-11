<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewUnitTestBase.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsBundle;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a base class for Views unit testing.
 *
 * Use this test class for unit tests of Views functionality. If a test
 * requires the full web test environment provided by WebTestBase, extend
 * ViewTestBase instead.
 *
 * @see \Drupal\views\Tests\ViewTestBase
 */
abstract class ViewUnitTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'views', 'views_test_config', 'views_test_data');

  protected function setUp() {
    parent::setUp();

    $this->setUpFixtures();
  }

  /**
   * Sets up the configuration and schema of views and views_test_data modules.
   *
   * Because the schema of views_test_data.module is dependent on the test
   * using it, it cannot be enabled normally.
   */
  protected function setUpFixtures() {
    // Define the schema and views data variable before enabling the test module.
    \Drupal::state()->set('views_test_data_schema', $this->schemaDefinition());
    \Drupal::state()->set('views_test_data_views_data', $this->viewsData());

    $this->installConfig(array('views', 'views_test_config', 'views_test_data'));
    foreach ($this->schemaDefinition() as $table => $schema) {
      $this->installSchema('views_test_data', $table);
    }

    // The router table is required for router rebuilds.
    $this->installSchema('system', array('router'));
    \Drupal::service('router.builder')->rebuild();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();

    // Tests implementing ViewUnitTestBase depend on the theme system being
    // properly configured.
    $this->installConfig(array('system'));
    ViewTestData::createTestViews(get_class($this), array('views_test_config'));
  }

  /**
   * Verifies that a result set returned by a View matches expected values.
   *
   * The comparison is done on the string representation of the columns of the
   * column map, taking the order of the rows into account, but not the order
   * of the columns.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $expected_result
   *   An expected result set.
   * @param array $column_map
   *   (optional) An associative array mapping the columns of the result set
   *   from the view (as keys) and the expected result set (as values).
   * @param string $message
   *   (optional) A custom message to display with the assertion. Defaults to
   *   'Identical result set.'
   *
   * @return bool
   *   TRUE if the assertion succeeded, or FALSE otherwise.
   */
  protected function assertIdenticalResultset($view, $expected_result, $column_map = array(), $message = NULL) {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, 'assertIdentical', $message);
  }

  /**
   * Verifies that a result set returned by a View differs from certain values.
   *
   * Inverse of ViewsTestCase::assertIdenticalResultset().
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $expected_result
   *   An expected result set.
   * @param array $column_map
   *   (optional) An associative array mapping the columns of the result set
   *  from the view (as keys) and the expected result set (as values).
   * @param string $message
   *   (optional) A custom message to display with the assertion. Defaults to
   *   'Non-identical result set.'
   *
   * @return bool
   *   TRUE if the assertion succeeded, or FALSE otherwise.
   */
  protected function assertNotIdenticalResultset($view, $expected_result, $column_map = array(), $message = NULL) {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, 'assertNotIdentical', $message);
  }

  /**
   * Performs View result assertions.
   *
   * This is a helper method for ViewTestBase::assertIdenticalResultset() and
   * ViewTestBase::assertNotIdenticalResultset().
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $expected_result
   *   An expected result set.
   * @param array $column_map
   *   An associative array mapping the columns of the result set
   *   from the view (as keys) and the expected result set (as values).
   * @param string $assert_method
   *   The TestBase assertion method to use (either 'assertIdentical' or
   *   'assertNotIdentical').
   * @param string $message
   *   (optional) The message to display with the assertion.
   *
   * @return bool
   *   TRUE if the assertion succeeded, or FALSE otherwise.
   *
   * @see \Drupal\views\Tests\ViewTestBase::assertIdenticalResultset()
   * @see \Drupal\views\Tests\ViewTestBase::assertNotIdenticalResultset()
   */
  protected function assertIdenticalResultsetHelper($view, $expected_result, $column_map, $assert_method, $message = NULL) {
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

    $this->verbose('<pre style="white-space: pre-wrap;">'
      . "\n\nQuery:\n" . $view->build_info['query']
      . "\n\nQuery arguments:\n" . var_export($view->build_info['query_args'], TRUE)
      . "\n\nActual result:\n" . var_export($result, TRUE)
      . "\n\nExpected result:\n" . var_export($expected_result, TRUE));

    // Reset the numbering of the arrays.
    $result = array_values($result);
    $expected_result = array_values($expected_result);

    // Do the actual comparison.
    if (!isset($message)) {
      $not = (strpos($assert_method, 'Not') ? 'not' : '');
      $message = format_string("Actual result <pre>\n@actual\n</pre> is $not identical to expected <pre>\n@expected\n</pre>", array(
        '@actual' => var_export($result, TRUE),
        '@expected' => var_export($expected_result, TRUE),
      ));
    }
    return $this->$assert_method($result, $expected_result, $message);
  }

  /**
   * Orders a nested array containing a result set based on a given column.
   *
   * @param array $result_set
   *   An array of rows from a result set, with each row as an associative
   *   array keyed by column name.
   * @param string $column
   *   The column name by which to sort the result set.
   * @param bool $reverse
   *   (optional) Boolean indicating whether to sort the result set in reverse
   *   order. Defaults to FALSE.
   *
   * @return array
   *   The sorted result set.
   */
  protected function orderResultSet($result_set, $column, $reverse = FALSE) {
    $order = $reverse ? -1 : 1;
    usort($result_set, function ($a, $b) use ($column, $order) {
      if ($a[$column] == $b[$column]) {
        return 0;
      }
      return $order * (($a[$column] < $b[$column]) ? -1 : 1);
    });
    return $result_set;
  }

  /**
   * Executes a view with debugging.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $args
   *   (optional) An array of the view arguments to use for the view.
   */
  protected function executeView($view, $args = array()) {
    $view->setDisplay();
    $view->preExecute($args);
    $view->execute();
    $verbose_message = '<pre>Executed view: ' . ((string) $view->build_info['query']). '</pre>';
    if ($view->build_info['query'] instanceof SelectInterface) {
      $verbose_message .= '<pre>Arguments: ' . print_r($view->build_info['query']->getArguments(), TRUE) . '</pre>';
    }
    $this->verbose($verbose_message);
  }

  /**
   * Returns the schema definition.
   */
  protected function schemaDefinition() {
    return ViewTestData::schemaDefinition();
  }

  /**
   * Returns the views data definition.
   */
  protected function viewsData() {
    return ViewTestData::viewsData();
  }

  /**
   * Returns a very simple test dataset.
   */
  protected function dataSet() {
    return ViewTestData::dataSet();
  }

}
