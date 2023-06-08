<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Defines a base class for Views kernel testing.
 */
abstract class ViewsKernelTestBase extends KernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * Views to be enabled.
   *
   * Test classes should override this property and provide the list of testing
   * views.
   *
   * @var array
   */
  public static $testViews = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'system',
    'views',
    'views_test_config',
    'views_test_data',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * @param bool $import_test_views
   *   Should the views specified on the test class be imported. If you need
   *   to setup some additional stuff, like fields, you need to call false and
   *   then call createTestViews for your own.
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->setUpFixtures();

    if ($import_test_views) {
      ViewTestData::createTestViews(static::class, ['views_test_config']);
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
    $this->installConfig(['system']);

    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    // Define the schema and views data variable before enabling the test module.
    $state->set('views_test_data_schema', $this->schemaDefinition());
    $state->set('views_test_data_views_data', $this->viewsData());
    $this->container->get('views.views_data')->clear();

    $this->installConfig(['views', 'views_test_config', 'views_test_data']);
    foreach ($this->schemaDefinition() as $table => $schema) {
      $this->installSchema('views_test_data', $table);
    }

    $this->container->get('router.builder')->rebuild();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = Database::getConnection()->insert('views_test_data')
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
      return $order * ($a[$column] <=> $b[$column]);
    });
    return $result_set;
  }

  /**
   * Executes a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $args
   *   (optional) An array of the view arguments to use for the view.
   */
  protected function executeView($view, array $args = []) {
    $view->setDisplay();
    $view->preExecute($args);
    $view->execute();
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
