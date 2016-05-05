<?php

namespace Drupal\views\Tests;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\simpletest\KernelTestBase;

/**
 * Defines a base class for Views unit testing.
 *
 * Use this test class for unit tests of Views functionality. If a test
 * requires the full web test environment provided by WebTestBase, extend
 * ViewTestBase instead.
 *
 * @deprecated in Drupal 8.0.x, will be removed in Drupal 8.2.x. Use
 *   \Drupal\Tests\views\Kernel\ViewsKernelTestBase instead.
 *
 * @see \Drupal\Tests\views\Kernel\ViewsKernelTestBase
 */
abstract class ViewKernelTestBase extends KernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'views', 'views_test_config', 'views_test_data', 'user');

  /**
   * {@inheritdoc}
   *
   * @param bool $import_test_views
   *   Should the views specififed on the test class be imported. If you need
   *   to setup some additional stuff, like fields, you need to call false and
   *   then call createTestViews for your own.
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->installSchema('system', array('sequences'));
    $this->setUpFixtures();

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), array('views_test_config'));
    }
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

    \Drupal::service('router.builder')->rebuild();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
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
  protected function executeView($view, array $args = array()) {
    $view->setDisplay();
    $view->preExecute($args);
    $view->execute();
    $verbose_message = '<pre>Executed view: ' . ((string) $view->build_info['query']) . '</pre>';
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
