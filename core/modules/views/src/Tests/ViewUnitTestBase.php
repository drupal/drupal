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

  use ViewResultAssertionTrait;

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
    // First install the system module. Many Views have Page displays have menu
    // links, and for those to work, the system menus must already be present.
    $this->installConfig(array('system'));

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

    ViewTestData::createTestViews(get_class($this), array('views_test_config'));
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
