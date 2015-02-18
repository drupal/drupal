<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FilterBooleanOperatorStringTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\BooleanOperatorString
 * handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\BooleanOperatorString
 */
class FilterBooleanOperatorStringTest extends ViewUnitTestBase {

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_id' => 'id',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', array('key_value_expire'));
  }


  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['status'] = array(
      'description' => 'The status of this record',
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $views_data = parent::viewsData();

    $views_data['views_test_data']['status']['filter']['id'] = 'boolean_string';

    return $views_data;
  }

  /**
   * {@inheritdoc}
   */
  protected function dataSet() {
    $data = parent::dataSet();

    foreach ($data as &$row) {
      if ($row['status']) {
        $row['status'] = 'Enabled';
      }
      else {
        $row['status'] = '';
      }
    }

    return $data;
  }

  /**
   * Tests the BooleanOperatorString filter.
   */
  public function testFilterBooleanOperatorString() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'status' => array(
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 0,
      ),
    ));
    $this->executeView($view);

    $expected_result = array(
      array('id' => 2),
      array('id' => 4),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();
    $view->setDisplay();

    // Add the status boolean filter.
    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'status' => array(
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 1,
      ),
    ));
    $this->executeView($view);

    $expected_result = array(
      array('id' => 1),
      array('id' => 3),
      array('id' => 5),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Tests the Boolean filter with grouped exposed form enabled.
   */
  public function testFilterGroupedExposed() {
    $filters = $this->getGroupedExposedFilters();
    $view = Views::getView('test_view');

    $view->setExposedInput(array('status' => 1));
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array('id' => 1),
      array('id' => 3),
      array('id' => 5),
    );

    $this->assertEqual(3, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    $view->setExposedInput(array('status' => 2));
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);

    $expected_result = array(
      array('id' => 2),
      array('id' => 4),
    );

    $this->assertEqual(2, count($view->result));
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Provides grouped exposed filter configuration.
   *
   * @return array
   *   Returns the filter configuration for exposed filters.
   */
  protected function getGroupedExposedFilters() {
    $filters = array(
      'status' => array(
        'id' => 'status',
        'table' => 'views_test_data',
        'field' => 'status',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => array(
          'operator' => 'status_op',
          'label' => 'status',
          'identifier' => 'status',
        ),
        'is_grouped' => TRUE,
        'group_info' => array(
          'label' => 'status',
          'identifier' => 'status',
          'default_group' => 'All',
          'group_items' => array(
            1 => array(
              'title' => 'Active',
              'operator' => '=',
              'value' => '1',
            ),
            2 => array(
              'title' => 'Blocked',
              'operator' => '=',
              'value' => '0',
            ),
          ),
        ),
      ),
    );
    return $filters;
  }

}
