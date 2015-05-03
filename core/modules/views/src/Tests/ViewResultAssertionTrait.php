<?php

/**
 * @file
 * contains Drupal\views\Tests\ViewResultAssertionTrait.
 */

namespace Drupal\views\Tests;

use Drupal\views\Plugin\views\field\Field;
use Drupal\views\ViewExecutable;

/**
 * Provides a class for assertions to check for the expected result of a View.
 */
trait ViewResultAssertionTrait {

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
   */
  protected function assertIdenticalResultsetHelper($view, $expected_result, $column_map, $assert_method, $message = NULL) {
    // Convert $view->result to an array of arrays.
    $result = array();
    foreach ($view->result as $key => $value) {
      $row = array();
      foreach ($column_map as $view_column => $expected_column) {
        if (property_exists($value, $view_column)) {
          $row[$expected_column] = (string) $value->$view_column;
        }
        // The comparison will be done on the string representation of the value.
        // For entity fields we don't have the raw value. Let's try to fetch it
        // using the entity itself.
        elseif (empty($value->$view_column) && isset($view->field[$expected_column]) && ($field = $view->field[$expected_column]) && $field instanceof Field) {
          $column = NULL;
          if (count(explode(':', $view_column)) == 2) {
            $column = explode(':', $view_column)[1];
          }
          $row[$expected_column] = $field->getValue($value, $column);
        }
      }
      $result[$key] = $row;
    }

    // Remove the columns we don't need from the expected result.
    foreach ($expected_result as $key => $value) {
      $row = array();
      foreach ($column_map as $expected_column) {
        // The comparison will be done on the string representation of the value.
        if (is_object($value)) {
          $row[$expected_column] = (string) $value->$expected_column;
        }
        // This case is about fields with multiple values.
        elseif (is_array($value[$expected_column])) {
          foreach (array_keys($value[$expected_column]) as $delta) {
            $row[$expected_column][$delta] = (string) $value[$expected_column][$delta];
          }
        }
        else {
          $row[$expected_column] = (string) $value[$expected_column];
        }
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

}
