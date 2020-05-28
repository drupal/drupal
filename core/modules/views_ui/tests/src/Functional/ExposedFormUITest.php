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
  public static $modules = [
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

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }

    // Error strings used in the grouped filter form validation.
    $this->groupFormUiErrors['missing_value'] = t('A value is required if the label for this item is defined.');
    $this->groupFormUiErrors['missing_title'] = t('A label is required if the value for this item is defined.');
    $this->groupFormUiErrors['missing_title_empty_operator'] = t('A label is required for the specified operator.');
  }

  /**
   * Tests the admin interface of exposed filter and sort items.
   */
  public function testExposedAdminUi() {
    $edit = [];

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    // Be sure that the button is called exposed.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose filter'));

    // The first time the filter UI is displayed, the operator and the
    // value forms should be shown.
    $this->assertFieldById('edit-options-operator-in', 'in', 'Operator In exists');
    $this->assertFieldById('edit-options-operator-not-in', 'not in', 'Operator Not In exists');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists');

    // Click the Expose filter button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', $edit, t('Expose filter'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide filter'));

    // After exposing the filter, Operator and Value should be still here.
    $this->assertFieldById('edit-options-operator-in', 'in', 'Operator In exists');
    $this->assertFieldById('edit-options-operator-not-in', 'not in', 'Operator Not In exists');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists');

    // Check the validations of the filter handler.
    $edit = [];
    $edit['options[expose][identifier]'] = '';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText(t('The identifier is required if the filter is exposed.'));

    $edit = [];
    $edit['options[expose][identifier]'] = 'value';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText(t('This identifier is not allowed.'));

    // Now check the sort criteria.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created');
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose sort'));
    $this->assertNoFieldById('edit-options-expose-label', '', 'Make sure no label field is shown');

    // Un-expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalPostForm(NULL, [], t('Hide filter'));

    // After Un-exposing the filter, Operator and Value should be shown again.
    $this->assertFieldById('edit-options-operator-in', 'in', 'Operator In exists after hide filter');
    $this->assertFieldById('edit-options-operator-not-in', 'not in', 'Operator Not In exists after hide filter');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists after hide filter');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists after hide filter');

    // Click the Expose sort button.
    $edit = [];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created', $edit, t('Expose sort'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide sort'));
    $this->assertFieldById('edit-options-expose-label', 'Authored on', 'Make sure a label field is shown');

    // Test adding a new exposed sort criteria.
    $view_id = $this->randomView()['id'];
    $this->drupalGet("admin/structure/views/nojs/add-handler/$view_id/default/sort");
    $this->drupalPostForm(NULL, ['name[node_field_data.created]' => 1], t('Add and configure @handler', ['@handler' => t('sort criteria')]));
    $this->assertFieldByXPath('//input[@name="options[order]" and @checked="checked"]', 'ASC', 'The default order is set.');
    // Change the order and expose the sort.
    $this->drupalPostForm(NULL, ['options[order]' => 'DESC'], t('Apply'));
    $this->drupalPostForm("admin/structure/views/nojs/handler/$view_id/default/sort/created", [], t('Expose sort'));
    $this->assertFieldByXPath('//input[@name="options[order]" and @checked="checked"]', 'DESC');
    $this->assertFieldByName('options[expose][label]', 'Authored on', 'The default label is set.');
    // Change the label and save the view.
    $edit = ['options[expose][label]' => $this->randomString()];
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
    // Check that the values were saved.
    $display = View::load($view_id)->getDisplay('default');
    $this->assertTrue($display['display_options']['sorts']['created']['exposed']);
    $this->assertEqual($display['display_options']['sorts']['created']['expose'], ['label' => $edit['options[expose][label]']]);
    $this->assertEqual($display['display_options']['sorts']['created']['order'], 'DESC');
  }

  /**
   * Tests the admin interface of exposed grouped filters.
   */
  public function testGroupedFilterAdminUi() {
    $edit = [];

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');

    // Click the Expose filter button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', $edit, t('Expose filter'));
    // Check the label of the grouped filters button.
    $this->helperButtonHasLabel('edit-options-group-button-button', t('Grouped filters'));

    // Click the Grouped Filters button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalPostForm(NULL, [], t('Grouped filters'));

    // After click on 'Grouped Filters', the standard operator and value should
    // not be displayed.
    $this->assertNoFieldById('edit-options-operator-in', 'in', 'Operator In not exists');
    $this->assertNoFieldById('edit-options-operator-not-in', 'not in', 'Operator Not In not exists');
    $this->assertNoFieldById('edit-options-value-page', '', 'Checkbox for Page not exists');
    $this->assertNoFieldById('edit-options-value-article', '', 'Checkbox for Article not exists');

    // Check that after click on 'Grouped Filters', a new button is shown to
    // add more items to the list.
    $this->helperButtonHasLabel('edit-options-group-info-add-group', t('Add another item'));

    // Validate a single entry for a grouped filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default');
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
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default', [], 'Correct validation of the node type filter.');
    $this->assertNoGroupedFilterErrors();

    // Validate an "is empty" filter -- title without value is valid.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/body_value');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'No body';
    $edit["options[group_info][group_items][1][operator]"] = 'empty';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default', [], 'The "empty" operator validates correctly.');
    $this->assertNoGroupedFilterErrors();

    // Ensure the string "0" can be used as a value for numeric filters.
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_exposed_admin_ui/default/filter', ['name[node_field_data.nid]' => TRUE], t('Add and configure @handler', ['@handler' => t('filter criteria')]));
    $this->drupalPostForm(NULL, [], t('Expose filter'));
    $this->drupalPostForm(NULL, [], t('Grouped filters'));
    $edit = [];
    $edit['options[group_info][group_items][1][title]'] = 'Testing zero';
    $edit['options[group_info][group_items][1][operator]'] = '>';
    $edit['options[group_info][group_items][1][value][value]'] = '0';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default', [], 'A string "0" is a valid value.');
    $this->assertNoGroupedFilterErrors();

    // Ensure "between" filters validate correctly.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/nid');
    $edit['options[group_info][group_items][1][title]'] = 'ID between test';
    $edit['options[group_info][group_items][1][operator]'] = 'between';
    $edit['options[group_info][group_items][1][value][min]'] = '0';
    $edit['options[group_info][group_items][1][value][max]'] = '10';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default', [], 'The "between" filter validates correctly.');
    $this->assertNoGroupedFilterErrors();
  }

  public function testGroupedFilterAdminUiErrors() {
    // Select the empty operator without a title specified.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/body_value');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = '';
    $edit["options[group_info][group_items][1][operator]"] = 'empty';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText($this->groupFormUiErrors['missing_title_empty_operator']);

    // Specify a title without a value.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', [], t('Expose filter'));
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', [], t('Grouped filters'));
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText($this->groupFormUiErrors['missing_value']);

    // Specify a value without a title.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = [];
    $edit["options[group_info][group_items][1][title]"] = '';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText($this->groupFormUiErrors['missing_title']);
  }

  /**
   * Asserts that there are no Grouped Filters errors.
   *
   * @param string $message
   *   The assert message.
   * @param string $group
   *   The assertion group.
   *
   * @return bool
   *   Result of the assertion.
   */
  protected function assertNoGroupedFilterErrors($message = '', $group = 'Other') {
    foreach ($this->groupFormUiErrors as $error) {
      $err_message = $message;
      if (empty($err_message)) {
        $err_message = "Verify that '$error' is not in the HTML output.";
      }
      if (empty($message)) {
        return $this->assertNoRaw($error, $err_message, $group);
      }
    }
    return TRUE;
  }

  /**
  * Tests the configuration of grouped exposed filters.
  */
  public function testExposedGroupedFilter() {
    // Click the Expose filter button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', [], t('Expose filter'));
    // Select 'Grouped filters' radio button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', [], t('Grouped filters'));
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
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Check that the view is saved without errors.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    // Click the Expose filter button.
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_exposed_admin_ui/default/filter', ['name[node_field_data.status]' => 1], t('Add and configure filter criteria'));
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status', [], t('Expose filter'));
    // Select 'Grouped filters' radio button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status', [], t('Grouped filters'));
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
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Check that the view is saved without errors.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/status');
    // Assert the same settings defined before still are there.
    $this->assertFieldChecked('edit-options-group-info-group-items-1-value-all');
    $this->assertFieldChecked('edit-options-group-info-group-items-2-value-1');
    $this->assertFieldChecked('edit-options-group-info-group-items-3-value-0');
  }

}
