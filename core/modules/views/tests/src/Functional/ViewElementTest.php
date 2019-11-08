<?php

namespace Drupal\Tests\views\Functional;

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
  public static $testViews = ['test_view_embed'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
  }

  /**
   * Tests the rendered output and form output of a view element.
   */
  public function testViewElement() {
    $view = Views::getView('test_view_embed');
    $view->setDisplay();
    // Test a form.
    $this->drupalGet('views_test_data_element_form');

    $xpath = $this->xpath('//div[@class="views-element-container js-form-wrapper form-wrapper"]');
    $this->assertNotEmpty($xpath, 'The view container has been found on the form.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertNotEmpty($xpath, 'The view content has been found on the form.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Add an argument and save the view.
    $view->displayHandlers->get('default')->overrideOption('arguments', [
      'age' => [
        'default_action' => 'ignore',
        'title' => '',
        'default_argument_type' => 'fixed',
        'validate' => [
          'type' => 'none',
          'fail' => 'not found',
        ],
        'break_phrase' => FALSE,
        'not' => FALSE,
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'plugin_id' => 'numeric',
      ],
    ]);
    $view->save();

    // Test that the form has the expected result.
    $this->drupalGet('views_test_data_element_form');
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);
  }

  /**
   * Tests the rendered output and form output of a view element, using the
   * embed display plugin.
   */
  public function testViewElementEmbed() {
    $view = Views::getView('test_view_embed');
    $view->setDisplay();
    // Test a form.
    $this->drupalGet('views_test_data_element_embed_form');

    $xpath = $this->xpath('//div[@class="views-element-container js-form-wrapper form-wrapper"]');
    $this->assertNotEmpty($xpath, 'The view container has been found on the form.');

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertNotEmpty($xpath, 'The view content has been found on the form.');
    // There should be 5 rows in the results.
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 5);

    // Add an argument and save the view.
    $view->displayHandlers->get('default')->overrideOption('arguments', [
      'age' => [
        'default_action' => 'ignore',
        'title' => '',
        'default_argument_type' => 'fixed',
        'validate' => [
          'type' => 'none',
          'fail' => 'not found',
        ],
        'break_phrase' => FALSE,
        'not' => FALSE,
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'plugin_id' => 'numeric',
      ],
    ]);
    $view->save();

    // Test that the form has the same expected result.
    $this->drupalGet('views_test_data_element_embed_form');
    $xpath = $this->xpath('//div[@class="view-content"]/div');
    $this->assertEqual(count($xpath), 1);
  }

}
