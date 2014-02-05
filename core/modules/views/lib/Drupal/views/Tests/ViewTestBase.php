<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewTestBase.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a base class for Views testing in the full web test environment.
 *
 * Use this base test class if you need to emulate a full Drupal installation.
 * When possible, ViewUnitTestBase should be used instead. Both base classes
 * include the same methods.
 *
 * @see \Drupal\views\Tests\ViewUnitTestBase
 * @see \Drupal\simpletest\WebTestBase
 */
abstract class ViewTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'views_test_config');

  protected function setUp() {
    parent::setUp();

    // Ensure that the plugin definitions are cleared.
    foreach (ViewExecutable::getPluginTypes() as $plugin_type) {
      $this->container->get("plugin.manager.views.$plugin_type")->clearCachedDefinitions();
    }
    ViewTestData::createTestViews(get_class($this), array('views_test_config'));
  }

  /**
   * Sets up the views_test_data.module.
   *
   * Because the schema of views_test_data.module is dependent on the test
   * using it, it cannot be enabled normally.
   */
  protected function enableViewsTestModule() {
    // Define the schema and views data variable before enabling the test module.
    \Drupal::state()->set('views_test_data_schema', $this->schemaDefinition());
    \Drupal::state()->set('views_test_data_views_data', $this->viewsData());

    \Drupal::moduleHandler()->install(array('views_test_data'));
    $this->resetAll();
    $this->rebuildContainer();
    $this->container->get('module_handler')->reload();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
    $this->checkPermissions(array(), TRUE);
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
  protected function assertIdenticalResultset($view, $expected_result, $column_map = array(), $message = 'Identical result set.') {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, $message, 'assertIdentical');
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
  protected function assertNotIdenticalResultset($view, $expected_result, $column_map = array(), $message = 'Non-identical result set.') {
    return $this->assertIdenticalResultsetHelper($view, $expected_result, $column_map, $message, 'assertNotIdentical');
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
   * @param string $message
   *   The message to display with the assertion.
   * @param string $assert_method
   *   The TestBase assertion method to use (either 'assertIdentical' or
   *   'assertNotIdentical').
   *
   * @return bool
   *   TRUE if the assertion succeeded, or FALSE otherwise.
   *
   * @see \Drupal\views\Tests\ViewTestBase::assertIdenticalResultset()
   * @see \Drupal\views\Tests\ViewTestBase::assertNotIdenticalResultset()
   */
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
   * Asserts the existence of a button with a certain ID and label.
   *
   * @param string $id
   *   The HTML ID of the button
   * @param string $label.
   *   The expected label for the button.
   * @param string $message
   *   (optional) A custom message to display with the assertion. If no custom
   *   message is provided, the message will indicate the button label.
   *
   * @return bool
   *   TRUE if the asserion was successful, or FALSE on failure.
   */
  protected function helperButtonHasLabel($id, $expected_label, $message = 'Label has the expected value: %label.') {
    return $this->assertFieldById($id, $expected_label, t($message, array('%label' => $expected_label)));
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
    // A view does not really work outside of a request scope, due to many
    // dependencies like the current user.
    $this->container->enterScope('request');
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
