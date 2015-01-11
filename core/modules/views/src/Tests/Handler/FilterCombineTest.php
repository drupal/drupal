<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterCombineTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the combine filter handler.
 *
 * @group views
 */
class FilterCombineTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  protected $column_map = array(
    'views_test_data_name' => 'name',
    'views_test_data_job' => 'job',
  );

  public function testFilterCombineContains() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'job' => array(
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ),
    ));

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => array(
          'name',
          'job',
        ),
        'value' => 'ing',
      ),
    ));

    $this->executeView($view);
    $resultset = array(
      array(
        'name' => 'John',
        'job' => 'Singer',
      ),
      array(
        'name' => 'George',
        'job' => 'Singer',
      ),
      array(
        'name' => 'Ringo',
        'job' => 'Drummer',
      ),
      array(
        'name' => 'Ginger',
        'job' => NULL,
      ),
    );
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  /**
   * Tests if the filter can handle removed fields.
   *
   * Tests the combined filter handler when a field overwrite is done
   * and fields set in the combine filter are removed from the display
   * but not from the combined filter settings.
   */
  public function testFilterCombineContainsFieldsOverwritten() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'job' => array(
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ),
    ));

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'age' => array(
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => array(
          'name',
          'job',
          // Add a dummy field to the combined fields to simulate
          // a removed or deleted field.
          'dummy',
        ),
        'value' => 'ing',
      ),
    ));

    $this->executeView($view);
    // Make sure this view will not get displayed.
    $this->assertTrue($view->build_info['fail'], "View build has been marked as failed.");
    // Make sure this view does not pass validation with the right error.
    $errors = $view->validate();
    $this->assertEqual(reset($errors['default']), t('Field %field set in %filter is not set in this display.', array('%field' => 'dummy', '%filter' => 'Global: Combine fields filter')));
  }

  /**
   * Additional data to test the NULL issue.
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    $data_set[] = array(
      'name' => 'Ginger',
      'age' => 25,
      'job' => NULL,
      'created' => gmmktime(0, 0, 0, 1, 2, 2000),
      'status' => 1,
    );
    return $data_set;
  }

  /**
   * Allow {views_test_data}.job to be NULL.
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    unset($schema['views_test_data']['fields']['job']['not null']);
    return $schema;
  }

}
