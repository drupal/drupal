<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewElementTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Views;

/**
 * Tests the view render element.
 *
 * @group views
 */
class ViewElementTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_embed');

  /**
   * The raw render data array to use in tests.
   *
   * @var array
   */
  protected $render;

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    // Set up a render array to use. We need to copy this as drupal_render
    // passes by reference.
    $this->render = array(
      'view' => array(
        '#type' => 'view',
        '#name' => 'test_view_embed',
        '#display_id' => 'default',
        '#arguments' => array(25),
        '#embed' => FALSE,
      ),
    );
  }

  /**
   * Tests the rendered output and form output of a view element.
   */
  public function testViewElement() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view_embed');
    $view->setDisplay();

    // Set the content as our rendered array.
    $render = $this->render;
    $this->setRawContent($renderer->renderRoot($render));

    $xpath = $this->xpath('//div[@class="views-element-container"]');
    $this->assertTrue($xpath, 'The view container has been found in the rendered output.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertTrue($xpath, 'The view content has been found in the rendered output.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Test a form.
    $this->drupalGet('views_test_data_element_form');

    $xpath = $this->xpath('//div[@class="views-element-container js-form-wrapper form-wrapper"]');
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
        'title' => '',
        'default_argument_type' => 'fixed',
        'validate' => array(
          'type' => 'none',
          'fail' => 'not found',
        ),
        'break_phrase' => FALSE,
        'not' => FALSE,
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'plugin_id' => 'numeric',
      )
    ));
    $view->save();

    // Test the render array again.
    $render = $this->render;
    $this->setRawContent($renderer->renderRoot($render));
    // There should be 1 row in the results, 'John' arg 25.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);

    // Test that the form has the same expected result.
    $this->drupalGet('views_test_data_element_form');
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);
  }

  /**
   * Tests the rendered output and form output of a view element, using the
   * embed display plugin.
   */
  public function testViewElementEmbed() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view_embed');
    $view->setDisplay('embed_1');

    // Set the content as our rendered array.
    $render = $this->render;
    $render['#embed'] = TRUE;
    $this->setRawContent($renderer->renderRoot($render));

    $xpath = $this->xpath('//div[@class="views-element-container"]');
    $this->assertTrue($xpath, 'The view container has been found in the rendered output.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertTrue($xpath, 'The view content has been found in the rendered output.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Test a form.
    $this->drupalGet('views_test_data_element_embed_form');

    $xpath = $this->xpath('//div[@class="views-element-container js-form-wrapper form-wrapper"]');
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
        'title' => '',
        'default_argument_type' => 'fixed',
        'validate' => array(
          'type' => 'none',
          'fail' => 'not found',
        ),
        'break_phrase' => FALSE,
        'not' => FALSE,
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'plugin_id' => 'numeric',
      )
    ));
    $view->save();

    // Test the render array again.
    $render = $this->render;
    $render['#embed'] = TRUE;
    $this->setRawContent($renderer->renderRoot($render));
    // There should be 1 row in the results, 'John' arg 25.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);

    // Test that the form has the same expected result.
    $this->drupalGet('views_test_data_element_embed_form');
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);
  }

}
