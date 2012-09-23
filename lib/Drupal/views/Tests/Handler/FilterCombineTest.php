<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FilterCombineTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the combine filter handler.
 */
class FilterCombineTest extends HandlerTestBase {

  var $column_map = array();

  public static function getInfo() {
    return array(
      'name' => 'Filter: Combine',
      'description' => 'Tests the combine filter handler.',
      'group' => 'Views Handlers',
    );
  }

  function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->column_map = array(
      'views_test_data_name' => 'name',
      'views_test_data_job' => 'job',
    );
  }

  protected function getBasicView() {
    $view = parent::getBasicView();
    $view->displayHandlers['default']->display['display_options']['fields']['job'] = array(
      'id' => 'job',
      'table' => 'views_test_data',
      'field' => 'job',
      'relationship' => 'none',
    );
    return $view;
  }

  public function testFilterCombineContains() {
    $view = $this->getView();

    // Change the filtering.
    $view->displayHandlers['default']->overrideOption('filters', array(
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
   * Additional data to test the NULL issue.
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    $data_set[] = array(
      'name' => 'Ginger',
      'age' => 25,
      'job' => NULL,
      'created' => gmmktime(0, 0, 0, 1, 2, 2000),
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
