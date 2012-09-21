<?php

/**
 * @file
 * Definition of Drupal\views\Tests\BasicTest.
 */

namespace Drupal\views\Tests;

/**
 * Basic test class for Views query builder tests.
 */
class BasicTest extends ViewTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Basic query tests',
      'description' => 'A basic query test for Views.',
      'group' => 'Views'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests a trivial result set.
   */
  public function testSimpleResultSet() {
    $view = $this->getView();

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(5, count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->dataSet(), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  /**
   * Tests filtering of the result set.
   */
  public function testSimpleFiltering() {
    $view = $this->getView();

    // Add a filter.
    $view->displayHandlers['default']->overrideOption('filters', array(
      'age' => array(
        'operator' => '<',
        'value' => array(
          'value' => '28',
          'min' => '',
          'max' => '',
        ),
        'group' => '0',
        'exposed' => FALSE,
        'expose' => array(
          'operator' => FALSE,
          'label' => '',
        ),
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Build the expected result.
    $dataset = array(
      array(
        'id' => 1,
        'name' => 'John',
        'age' => 25,
      ),
      array(
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ),
      array(
        'id' => 4,
        'name' => 'Paul',
        'age' => 26,
      ),
    );

    // Verify the result.
    $this->assertEqual(3, count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  /**
   * Tests simple argument.
   */
  public function testSimpleArgument() {
    $view = $this->getView();

    // Add a argument.
    $view->displayHandlers['default']->overrideOption('arguments', array(
      'age' => array(
        'default_action' => 'ignore',
        'style_plugin' => 'default_summary',
        'style_options' => array(),
        'wildcard' => 'all',
        'wildcard_substitution' => 'All',
        'title' => '',
        'breadcrumb' => '',
        'default_argument_type' => 'fixed',
        'default_argument' => '',
        'validate' => array(
          'type' => 'none',
          'fail' => 'not found',
        ),
        'break_phrase' => 0,
        'not' => 0,
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'validate_user_argument_type' => 'uid',
        'validate_user_roles' => array(
          '2' => 0,
        ),
        'relationship' => 'none',
        'default_options_div_prefix' => '',
        'default_argument_user' => 0,
        'default_argument_fixed' => '',
        'default_argument_php' => '',
        'validate_argument_node_type' => array(
          'page' => 0,
          'story' => 0,
        ),
        'validate_argument_node_access' => 0,
        'validate_argument_nid_type' => 'nid',
        'validate_argument_vocabulary' => array(),
        'validate_argument_type' => 'tid',
        'validate_argument_transform' => 0,
        'validate_user_restrict_roles' => 0,
        'validate_argument_php' => '',
      )
    ));

    $saved_view = clone $view;

    // Execute with a view
    $view->setArguments(array(27));
    $this->executeView($view);

    // Build the expected result.
    $dataset = array(
      array(
        'id' => 2,
        'name' => 'George',
        'age' => 27,
      ),
    );

    // Verify the result.
    $this->assertEqual(1, count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    // Test "show all" if no argument is present.
    $view = $saved_view->cloneView();
    $this->executeView($view);

    // Build the expected result.
    $dataset = $this->dataSet();

    $this->assertEqual(5, count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultSet($view, $dataset, array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

}
