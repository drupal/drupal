<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Tests the numeric filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\NumericFilter
 */
class FilterNumericWebTest extends UITestBase {
  use SchemaCheckTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the filter numeric UI.
   */
  public function testFilterNumericUI(): void {
    // Add a page display to the test_view to be able to test the filtering.
    $path = 'test_view-path';
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => $path], 'Apply');
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/structure/views/nojs/add-handler/test_view/default/filter');
    $this->submitForm(['name[views_test_data.age]' => TRUE], 'Add and configure filter criteria');

    $this->submitForm([], 'Expose filter');
    $this->submitForm([], 'Grouped filters');

    $edit = [];
    $edit['options[group_info][group_items][1][title]'] = 'Old';
    $edit['options[group_info][group_items][1][operator]'] = '>';
    $edit['options[group_info][group_items][1][value][value]'] = 27;
    $edit['options[group_info][group_items][2][title]'] = 'Young';
    $edit['options[group_info][group_items][2][operator]'] = '<=';
    $edit['options[group_info][group_items][2][value][value]'] = 27;
    $edit['options[group_info][group_items][3][title]'] = 'From 26 to 28';
    $edit['options[group_info][group_items][3][operator]'] = 'between';
    $edit['options[group_info][group_items][3][value][min]'] = 26;
    $edit['options[group_info][group_items][3][value][max]'] = 28;

    $this->submitForm($edit, 'Apply');

    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    foreach ($edit as $name => $value) {
      $this->assertSession()->fieldValueEquals($name, $value);
    }

    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Save');
    $this->assertConfigSchemaByName('views.view.test_view');

    // Test that the exposed filter works as expected.
    $this->drupalGet('test_view-path');
    $this->assertSession()->pageTextContains('John');
    $this->assertSession()->pageTextContains('Paul');
    $this->assertSession()->pageTextContains('Ringo');
    $this->assertSession()->pageTextContains('George');
    $this->assertSession()->pageTextContains('Meredith');
    $this->submitForm(['age' => '2'], 'Apply');
    $this->assertSession()->pageTextContains('John');
    $this->assertSession()->pageTextContains('Paul');
    $this->assertSession()->pageTextNotContains('Ringo');
    $this->assertSession()->pageTextContains('George');
    $this->assertSession()->pageTextNotContains('Meredith');

    // Change the filter to a single filter to test the schema when the operator
    // is not exposed.
    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    $this->submitForm([], 'Single filter');
    $edit = [];
    $edit['options[value][value]'] = 25;
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Save');
    $this->assertConfigSchemaByName('views.view.test_view');

    // Test that the filter works as expected.
    $this->drupalGet('test_view-path');
    $this->assertSession()->pageTextContains('John');
    $this->assertSession()->pageTextNotContains('Paul');
    $this->assertSession()->pageTextNotContains('Ringo');
    $this->assertSession()->pageTextNotContains('George');
    $this->assertSession()->pageTextNotContains('Meredith');
    $this->submitForm(['age' => '26'], 'Apply');
    $this->assertSession()->pageTextNotContains('John');
    $this->assertSession()->pageTextContains('Paul');
    $this->assertSession()->pageTextNotContains('Ringo');
    $this->assertSession()->pageTextNotContains('George');
    $this->assertSession()->pageTextNotContains('Meredith');

    // Change the filter to a 'between' filter to test if the label and
    // description are set for the 'minimum' filter element.
    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    $edit = [];
    $edit['options[expose][label]'] = 'Age between';
    $edit['options[expose][description]'] = 'Description of the exposed filter';
    $edit['options[operator]'] = 'between';
    $edit['options[value][min]'] = 26;
    $edit['options[value][max]'] = 28;
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Save');
    $this->assertConfigSchemaByName('views.view.test_view');

    $this->submitForm([], 'Update preview');
    // Check the field (wrapper) label.
    $this->assertSession()->elementTextContains('css', 'fieldset#edit-age-wrapper legend', 'Age between');
    // Check the min/max labels.
    $this->assertSession()->elementsCount('xpath', '//fieldset[contains(@id, "edit-age-wrapper")]//label[contains(@for, "edit-age-min") and contains(text(), "Min")]', 1);
    $this->assertSession()->elementsCount('xpath', '//fieldset[contains(@id, "edit-age-wrapper")]//label[contains(@for, "edit-age-max") and contains(text(), "Max")]', 1);
    // Check that the description is shown in the right place.
    $this->assertEquals('Description of the exposed filter', trim($this->cssSelect('#edit-age-wrapper--description')[0]->getText()));

    // Change to an operation that only requires one form element ('>').
    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    $edit = [];
    $edit['options[expose][label]'] = 'Age greater than';
    $edit['options[expose][description]'] = 'Description of the exposed filter';
    $edit['options[operator]'] = '>';
    $edit['options[value][value]'] = 1000;
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Save');
    $this->assertConfigSchemaByName('views.view.test_view');

    $this->submitForm([], 'Update preview');

    // Make sure the label is visible and that there's no fieldset wrapper.
    $this->assertSession()->elementsCount('xpath', '//label[contains(@for, "edit-age") and contains(text(), "Age greater than")]', 1);
    $this->assertSession()->elementNotExists('xpath', '//fieldset[contains(@id, "edit-age-wrapper")]');
  }

}
