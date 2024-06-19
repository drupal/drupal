<?php

declare(strict_types=1);

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
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests the rendered output and form output of a view element.
   */
  public function testViewElement(): void {
    $view = Views::getView('test_view_embed');
    $view->setDisplay();
    // Test a form.
    $this->drupalGet('views_test_data_element_form');
    // Verify that the view container has been found on the form.
    $this->assertSession()->elementExists('xpath', '//div[@class="views-element-container js-form-wrapper form-wrapper"]');
    // Verify that the view content has been found on the form.
    $this->assertSession()->elementExists('xpath', '//div[@class="view-content"]');
    // There should be 5 rows in the results.
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]/div', 5);

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
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]/div', 1);
  }

  /**
   * Tests the rendered output and form output of the "embed" display plugin.
   */
  public function testViewElementEmbed(): void {
    $view = Views::getView('test_view_embed');
    $view->setDisplay();
    // Test a form.
    $this->drupalGet('views_test_data_element_embed_form');
    // Verify that the view container has been found on the form.
    $this->assertSession()->elementExists('xpath', '//div[@class="views-element-container js-form-wrapper form-wrapper"]');
    // Verify that the view content has been found on the form.
    $this->assertSession()->elementExists('xpath', '//div[@class="view-content"]');
    // There should be 5 rows in the results.
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]/div', 5);

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
    $this->assertSession()->elementsCount('xpath', '//div[@class="view-content"]/div', 1);
  }

}
