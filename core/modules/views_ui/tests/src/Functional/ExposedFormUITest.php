<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Entity\View;

/**
 * Tests exposed forms UI functionality.
 *
 * @group views_ui
 */
class ExposedFormUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_exposed_admin_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views_ui',
    'block',
    'taxonomy',
    'field_ui',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Array of error message strings raised by the grouped form.
   *
   * @var array
   *
   * @see FilterPluginBase::buildGroupValidate
   */
  protected $groupFormUiErrors = [];

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }

    // Error strings used in the grouped filter form validation.
    $this->groupFormUiErrors['missing_value'] = 'A value is required if the label for this item is defined.';
    $this->groupFormUiErrors['missing_title'] = 'A label is required if the value for this item is defined.';
    $this->groupFormUiErrors['missing_title_empty_operator'] = 'A label is required for the specified operator.';
  }

  /**
   * Tests the admin interface of exposed filter and sort items.
   */
  public function testExposedAdminUi() {
    $edit = [];

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    // Be sure that the button is called exposed.
    $this->helperButtonHasLabel('edit-options-expose-button-button', 'Expose filter');

    // The first time the filter UI is displayed, the operator and the
    // value forms should be shown.
    $this->assertSession()->fieldValueEquals('edit-options-operator-in', 'in');
    $this->assertSession()->fieldValueEquals('edit-options-operator-not-in', 'in');
    $this->assertSession()->checkboxNotChecked('edit-options-value-page');
    $this->assertSession()->checkboxNotChecked('edit-options-value-article');

    // Click the Expose filter button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm($edit, 'Expose filter');
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', 'Hide filter');

    // After exposing the filter, Operator and Value should be still here.
    $this->assertSession()->fieldValueEquals('edit-options-operator-in', 'in');
    $this->assertSession()->fieldValueEquals('edit-options-operator-not-in', 'in');
    $this->assertSession()->checkboxNotChecked('edit-options-value-page');
    $this->assertSession()->checkboxNotChecked('edit-options-value-article');

    // Check the validations of the filter handler.
    $edit = [];
    $edit['options[expose][identifier]'] = '';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('The identifier is required if the filter is exposed.');

    $edit = [];
    $edit['options[expose][identifier]'] = 'value';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('This identifier is not allowed.');

    // Now check the sort criteria.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created');
    $this->helperButtonHasLabel('edit-options-expose-button-button', 'Expose sort');
    $this->assertSession()->fieldNotExists('edit-options-expose-label');
    $this->assertSession()->fieldNotExists('Sort field identifier');

    // Un-expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Hide filter');

    // After Un-exposing the filter, Operator and Value should be shown again.
    $this->assertSession()->fieldValueEquals('edit-options-operator-in', 'in');
    $this->assertSession()->fieldValueEquals('edit-options-operator-not-in', 'in');
    $this->assertSession()->checkboxNotChecked('edit-options-value-page');
    $this->assertSession()->checkboxNotChecked('edit-options-value-article');

    // Click the Expose sort button.
    $edit = [];
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created');
    $this->submitForm($edit, 'Expose sort');
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', 'Hide sort');
    $this->assertSession()->fieldValueEquals('edit-options-expose-label', 'Authored on');
    $this->assertSession()->fieldValueEquals('Sort field identifier', 'created');

    // Test adding a new exposed sort criteria.
    $view_id = $this->randomView()['id'];
    $this->drupalGet("admin/structure/views/nojs/add-handler/$view_id/default/sort");
    $this->submitForm(['name[node_field_data.created]' => 1], 'Add and configure sort criteria');
    $this->assertSession()->fieldValueEquals('options[order]', 'ASC');
    // Change the order and expose the sort.
    $this->submitForm(['options[order]' => 'DESC'], 'Apply');
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/sort/created");
    $this->submitForm([], 'Expose sort');
    $this->assertSession()->fieldValueEquals('options[order]', 'DESC');
    $this->assertSession()->fieldValueEquals('options[expose][label]', 'Authored on');
    $this->assertSession()->fieldValueEquals('Sort field identifier', 'created');

    // Change the label and try with an empty identifier.
    $edit = [
      'options[expose][label]' => $this->randomString(),
      'options[expose][field_identifier]' => '',
    ];
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('Sort field identifier field is required.');

    // Try with an invalid identifier.
    $edit['options[expose][field_identifier]'] = 'abc&! ###08.';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('This identifier has illegal characters.');

    // Use a valid identifier.
    $edit['options[expose][field_identifier]'] = $this->randomMachineName() . '_-~.';
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Check that the values were saved.
    $display = View::load($view_id)->getDisplay('default');
    $this->assertTrue($display['display_options']['sorts']['created']['exposed']);
    $this->assertSame([
      'label' => $edit['options[expose][label]'],
      'field_identifier' => $edit['options[expose][field_identifier]'],
    ], $display['display_options']['sorts']['created']['expose']);
    $this->assertSame('DESC', $display['display_options']['sorts']['created']['order']);

    // Test the identifier uniqueness.
    $this->drupalGet("admin/structure/views/nojs/handler/{$view_id}/default/sort/created_1");
    $this->submitForm([], 'Expose sort');
    $this->submitForm([
      'options[expose][field_identifier]' => $edit['options[expose][field_identifier]'],
    ], 'Apply');
    $this->assertSession()->pageTextContains('This identifier is already used by Content: Authored on sort handler.');
  }

  /**
   * Tests the admin interface of exposed grouped filters.
   */
  public function testGroupedFilterAdminUi() {
    $edit = [];

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');

    // Click the Expose filter button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm($edit, 'Expose filter');
    // Check the label of the grouped filters button.
    $this->helperButtonHasLabel('edit-options-group-button-button', 'Grouped filters');

    // Click the Grouped Filters button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Grouped filters');

    // After click on 'Grouped Filters', the standard operator and value should
    // not be displayed.
    $this->assertSession()->fieldNotExists('edit-options-operator-in');
    $this->assertSession()->fieldNotExists('edit-options-operator-not-in');
    $this->assertSession()->fieldNotExists('edit-options-value-page');
    $this->assertSession()->fieldNotExists('edit-options-value-article');

    // Check that after click on 'Grouped Filters', a new button is shown to
    // add more items to the list.
    $this->helperButtonHasLabel('edit-options-group-info-add-group', 'Add another item');

    // Validate a single entry for a grouped filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_exposed_admin_ui/edit/default');
    $this->assertNoGroupedFilterErrors();

    // Validate multiple entries for grouped filters.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';
    $edit["options[group_info][group_items][2][title]"] = 'Is Page';
    $edit["options[group_info][group_items][2][value][page]"] = 'page';
    $edit["options[group_info][group_items][3][title]"] = 'Is Page and Article';
    $edit["options[group_info][group_items][3][value][article]"] = 'article';
    $edit["options[group_info][group_items][3][value][page]"] = 'page';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_exposed_admin_ui/edit/default');
    $this->assertNoGroupedFilterErrors();

    // Validate an "is empty" filter -- title without value is valid.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/body_value');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'No body';
    $edit["options[group_info][group_items][1][operator]"] = 'empty';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_exposed_admin_ui/edit/default');
    $this->assertNoGroupedFilterErrors();

    // Ensure the string "0" can be used as a value for numeric filters.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_exposed_admin_ui/default/filter');
    $this->submitForm(['name[node_field_data.nid]' => TRUE], 'Add and configure filter criteria');
    $this->submitForm([], 'Expose filter');
    $this->submitForm([], 'Grouped filters');
    $edit = [];
    $edit['options[group_info][group_items][1][title]'] = 'Testing zero';
    $edit['options[group_info][group_items][1][operator]'] = '>';
    $edit['options[group_info][group_items][1][value][value]'] = '0';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_exposed_admin_ui/edit/default');
    $this->assertNoGroupedFilterErrors();

    // Ensure "between" filters validate correctly.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/nid');
    $edit['options[group_info][group_items][1][title]'] = 'ID between test';
    $edit['options[group_info][group_items][1][operator]'] = 'between';
    $edit['options[group_info][group_items][1][value][min]'] = '0';
    $edit['options[group_info][group_items][1][value][max]'] = '10';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_exposed_admin_ui/edit/default');
    $this->assertNoGroupedFilterErrors();
  }

  public function testGroupedFilterAdminUiErrors() {
    // Select the empty operator without a title specified.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/body_value');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = '';
    $edit["options[group_info][group_items][1][operator]"] = 'empty';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains($this->groupFormUiErrors['missing_title_empty_operator']);

    // Specify a title without a value.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Expose filter');
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Grouped filters');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains($this->groupFormUiErrors['missing_value']);

    // Specify a value without a title.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = '';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains($this->groupFormUiErrors['missing_title']);
  }

  /**
   * Asserts that there are no Grouped Filters errors.
   *
   * @param string $message
   *   The assert message.
   *
   * @internal
   */
  protected function assertNoGroupedFilterErrors(string $message = ''): void {
    foreach ($this->groupFormUiErrors as $error) {
      if (empty($message)) {
        $this->assertSession()->responseNotContains($error);
      }
    }
  }

  /**
   * Tests the configuration of grouped exposed filters.
   */
  public function testExposedGroupedFilter() {
    // Click the Expose filter button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Expose filter');
    // Select 'Grouped filters' radio button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->submitForm([], 'Grouped filters');
    // Add 3 groupings.
    $edit = [
      'options[group_button][radios][radios]' => 1,
      'options[group_info][group_items][1][title]' => '1st',
      'options[group_info][group_items][1][value][all]' => 'all',
      'options[group_info][group_items][2][title]' => '2nd',
      'options[group_info][group_items][2][value][article]' => 'article',
      'options[group_info][group_items][3][title]' => '3rd',
      'options[group_info][group_items][3][value][page]' => 'page',
    ];
    // Apply the filter settings.
    $this->submitForm($edit, 'Apply');
    // Check that the view is saved without errors.
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Click the Expose filter button.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_exposed_admin_ui/default/filter');
    $this->submitForm(['name[node_field_data.status]' => 1], 'Add and configure filter criteria');
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status');
    $this->submitForm([], 'Expose filter');
    // Select 'Grouped filters' radio button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status');
    $this->submitForm([], 'Grouped filters');
    // Add 3 groupings.
    $edit = [
      'options[group_button][radios][radios]' => 1,
      'options[group_info][group_items][1][title]' => 'Any',
      'options[group_info][group_items][1][value]' => 'All',
      'options[group_info][group_items][2][title]' => 'Published',
      'options[group_info][group_items][2][value]' => 1,
      'options[group_info][group_items][3][title]' => 'Unpublished',
      'options[group_info][group_items][3][value]' => 0,
    ];
    // Apply the filter settings.
    $this->submitForm($edit, 'Apply');
    // Check that the view is saved without errors.
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status');
    // Assert the same settings defined before still are there.
    $this->assertSession()->checkboxChecked('edit-options-group-info-group-items-1-value-all');
    $this->assertSession()->checkboxChecked('edit-options-group-info-group-items-2-value-1');
    $this->assertSession()->checkboxChecked('edit-options-group-info-group-items-3-value-0');
  }

}
