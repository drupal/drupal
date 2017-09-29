<?php

namespace Drupal\views\Tests;
@trigger_error('\Drupal\views\Tests\ViewTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\views\Functional\ViewTestBase', E_USER_DEPRECATED);

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a base class for Views testing in the full web test environment.
 *
 * Use this base test class if you need to emulate a full Drupal installation.
 * When possible, ViewsKernelTestBase should be used instead. Both base classes
 * include the same methods.
 *
 * @deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Tests\views\Functional\ViewTestBase.
 *
 * @see \Drupal\Tests\views\Kernel\ViewsKernelTestBase
 * @see \Drupal\simpletest\WebTestBase
 */
abstract class ViewTestBase extends WebTestBase {

  use ViewResultAssertionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'views_test_config'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp();
    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['views_test_config']);
    }
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

    \Drupal::service('module_installer')->install(['views_test_data']);
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
   * @param string $expected_label
   *   The expected label for the button.
   * @param string $message
   *   (optional) A custom message to display with the assertion. If no custom
   *   message is provided, the message will indicate the button label.
   *
   * @return bool
   *   TRUE if the assertion was successful, or FALSE on failure.
   */
  protected function helperButtonHasLabel($id, $expected_label, $message = 'Label has the expected value: %label.') {
    return $this->assertFieldById($id, $expected_label, t($message, ['%label' => $expected_label]));
  }

  /**
   * Executes a view with debugging.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $args
   *   (optional) An array of the view arguments to use for the view.
   */
  protected function executeView(ViewExecutable $view, $args = []) {
    // A view does not really work outside of a request scope, due to many
    // dependencies like the current user.
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
   *
   * @internal
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
