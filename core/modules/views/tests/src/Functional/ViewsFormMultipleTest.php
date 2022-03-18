<?php

namespace Drupal\Tests\views\Functional;

/**
 * Tests a page with multiple Views forms.
 *
 * @group views
 */
class ViewsFormMultipleTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_form_multiple'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['field_form_button_test']['field'] = [
      'title' => 'Button test',
      'help' => 'Adds a test form button.',
      'id' => 'field_form_button_test',
    ];
    return $data;
  }

  /**
   * Tests the a page with multiple View forms in it.
   */
  public function testViewsFormMultiple() {
    // Get the test page.
    $this->drupalGet('views_test_form_multiple');

    $this->assertSession()->pageTextContains('Test base form ID with Views forms and arguments.');

    // Submit the forms, validate argument returned in message set by handler.
    // @note There is not a way to specify a specific index for a submit button. So
    // the row index returned is always the last occurrence.
    $this->getSession()->getPage()->pressButton('edit-field-form-button-test-4--2');
    $this->assertSession()->pageTextContains('The test button at row 4 for test_form_multiple (default) View with args: arg2 was submitted.');
    $this->getSession()->getPage()->pressButton('edit-field-form-button-test-4');
    $this->assertSession()->pageTextContains('The test button at row 4 for test_form_multiple (default) View with args: arg1 was submitted.');
  }

}
