<?php
/**
 * @file
 * Definition of Drupal\views\Tests\ViewTestBase.
 */

namespace Drupal\views\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Abstract class for views testing.
 */
abstract class ViewTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  /**
   * The view to use for the test.
   *
   * @var Drupal\views\ViewExecutable
   */
  protected $view;

  protected function setUp() {
    parent::setUp();

    $this->view = $this->getBasicView();
  }

  /**
   * Sets up the views_test_data.module.
   *
   * Because the schema of views_test_data.module is dependent on the test using it,
   * it cannot be enabled normally.
   */
  protected function enableViewsTestModule() {
    // Define the schema and views data variable before enabling the test module.
    state()->set('views_test_data_schema', $this->schemaDefinition());
    state()->set('views_test_data_views_data', $this->viewsData());

    module_enable(array('views_test_data'));
    $this->resetAll();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
    $this->checkPermissions(array(), TRUE);

    // Reset the test view, in case it was dependent on the test data module.
    $this->view = $this->getBasicView();
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
    $view->setDisplay();
    $view->preExecute($args);
    $view->execute();
    $this->verbose('<pre>Executed view: ' . ((string) $view->build_info['query']) . '</pre>');
  }

  /**
   * Build and return a Page view of the views_test_data table.
   *
   * @return view
   */
  protected function getBasicPageView() {
    $view = $this->getView();

    // In order to test exposed filters, we have to disable
    // the exposed forms cache.
    drupal_static_reset('views_exposed_form_cache');

    $display = $view->storage->newDisplay('page', 'Page', 'page_1');
    return $view;
  }

  /**
   * The schema definition.
   */
  protected function schemaDefinition() {
    $schema['views_test_data'] = array(
      'description' => 'Basic test table for Views tests.',
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'name' => array(
          'description' => "A person's name",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'age' => array(
          'description' => "The person's age",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'job' => array(
          'description' => "The person's job",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'Undefined',
        ),
        'created' => array(
          'description' => "The creation date of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('id'),
      'unique keys' => array(
        'name' => array('name')
      ),
      'indexes' => array(
        'ages' => array('age'),
      ),
    );
    return $schema;
  }

  /**
   * The views data definition.
   */
  protected function viewsData() {
    // Declaration of the base table.
    $data['views_test_data']['table'] = array(
      'group' => t('Views test'),
      'base' => array(
        'field' => 'id',
        'title' => t('Views test data'),
        'help' => t('Users who have created accounts on your site.'),
      ),
    );

    // Declaration of fields.
    $data['views_test_data']['id'] = array(
      'title' => t('ID'),
      'help' => t('The test data ID'),
      'field' => array(
        'id' => 'numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['name'] = array(
      'title' => t('Name'),
      'help' => t('The name of the person'),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['age'] = array(
      'title' => t('Age'),
      'help' => t('The age of the person'),
      'field' => array(
        'id' => 'numeric',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['job'] = array(
      'title' => t('Job'),
      'help' => t('The job of the person'),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['created'] = array(
      'title' => t('Created'),
      'help' => t('The creation date of this record'),
      'field' => array(
        'id' => 'date',
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'id' => 'date',
      ),
      'filter' => array(
        'id' => 'date',
      ),
      'sort' => array(
        'id' => 'date',
      ),
    );
    return $data;
  }

  /**
   * A very simple test dataset.
   */
  protected function dataSet() {
    return array(
      array(
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 1, 2000),
      ),
      array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 2, 2000),
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
        'created' => gmmktime(6, 30, 30, 1, 1, 2000),
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
        'created' => gmmktime(6, 0, 0, 1, 1, 2000),
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
        'created' => gmmktime(6, 30, 10, 1, 1, 2000),
      ),
    );
  }

  /**
   * Build and return a basic view of the views_test_data table.
   *
   * @return Drupal\views\ViewExecutable
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_view');
  }

  /**
   * Creates a new View instance by creating directly from config data.
   *
   * @param string $view_name
   *   The name of the test view to create.
   *
   * @return Drupal\views\ViewExecutable
   *   A View instance.
   */
  protected function createViewFromConfig($view_name) {
    if (!module_exists('views_test_config')) {
      module_enable(array('views_test_config'));
    }

    $data = config("views.view.$view_name")->get();

    $view = entity_create('view', $data);
    $view = $view->get('executable');
    $view->setDisplay();

    return $view;
  }

  /**
   * Clones the view used in this test and sets the default display.
   *
   * @param Drupal\views\Plugin\Core\Entity\View $original_view
   *   (optional) The view to clone. If not specified, the default view for the
   *   test will be used.
   *
   * @return Drupal\views\ViewExecutable
   *   A clone of the view.
   */
  protected function getView($original_view = NULL) {
    if (isset($original_view)) {
      $view = $original_view->cloneView();
    }
    else {
      $view = $this->view->cloneView();
    }
    $view->setDisplay();
    return $view;
  }

}
