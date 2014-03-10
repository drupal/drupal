<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewElementTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Views;

/**
 * Tests the 'view' element type.
 */
class ViewElementTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * The raw render data array to use in tests.
   *
   * @var array
   */
  protected $render;

  public static function getInfo() {
    return array(
      'name' => 'View element',
      'description' => 'Tests the view render element.',
      'group' => 'Views'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    // Set up a render array to use. We need to copy this as drupal_render
    // passes by reference.
    $this->render = array(
      'view' => array(
        '#type' => 'view',
        '#name' => 'test_view',
        '#display_id' => 'default',
        '#arguments' => array(25),
      ),
    );
  }

  /**
   * Tests the rendered output and form output of a view element.
   */
  public function testViewElement() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Set the content as our rendered array.
    $render = $this->render;
    $this->drupalSetContent(drupal_render($render));

    $xpath = $this->xpath('//div[@class="views-element-container"]');
    $this->assertTrue($xpath, 'The view container has been found in the rendered output.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertTrue($xpath, 'The view content has been found in the rendered output.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Test a form.
    $this->drupalGet('views_test_data_element_form');

    $xpath = $this->xpath('//div[@class="views-element-container form-wrapper"]');
    $this->assertTrue($xpath, 'The view container has been found on the form.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertTrue($xpath, 'The view content has been found on the form.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Add an argument and save the view.
    $view->displayHandlers->get('default')->overrideOption('arguments', array(
      'age' => array(
        'default_action' => 'ignore',
        'style_plugin' => 'default_summary',
        'style_options' => array(),
        'wildcard' => 'all',
        'wildcard_substitution' => 'All',
        'title' => '',
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
      )
    ));
    $view->save();

    // Test the render array again.
    $render = $this->render;
    $this->drupalSetContent(drupal_render($render));
    // There should be 1 row in the results, 'John' arg 25.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);

    // Test that the form has the same expected result.
    $this->drupalGet('views_test_data_element_form');
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);
  }

}
