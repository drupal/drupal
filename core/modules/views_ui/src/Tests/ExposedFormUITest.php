<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ExposedFormUITest.
 */

namespace Drupal\views_ui\Tests;

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
  public static $testViews = array('test_exposed_admin_ui');

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->drupalCreateContentType(array('type' => 'page'));

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }
  }

  /**
   * Tests the admin interface of exposed filter and sort items.
   */
  function testExposedAdminUi() {
    $edit = array();

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    // Be sure that the button is called exposed.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose filter'));

    // The first time the filter UI is displayed, the operator and the
    // value forms should be shown.
    $this->assertFieldById('edit-options-operator-in', '', 'Operator In exists');
    $this->assertFieldById('edit-options-operator-not-in', '', 'Operator Not In exists');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists');

    // Click the Expose filter button.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', $edit, t('Expose filter'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide filter'));
    // Check the label of the grouped exposed button
    $this->helperButtonHasLabel('edit-options-group-button-button', t('Grouped filters'));

    // After exposing the filter, Operator and Value should be still here.
    $this->assertFieldById('edit-options-operator-in', '', 'Operator In exists');
    $this->assertFieldById('edit-options-operator-not-in', '', 'Operator Not In exists');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists');

    // Check the validations of the filter handler.
    $edit = array();
    $edit['options[expose][identifier]'] = '';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText(t('The identifier is required if the filter is exposed.'));

    $edit = array();
    $edit['options[expose][identifier]'] = 'value';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertText(t('This identifier is not allowed.'));

    // Now check the sort criteria.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created');
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose sort'));
    $this->assertNoFieldById('edit-options-expose-label', '', 'Make sure no label field is shown');

    // Click the Grouped Filters button.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalPostForm(NULL, array(), t('Grouped filters'));

    // After click on 'Grouped Filters', the standard operator and value should
    // not be displayed.
    $this->assertNoFieldById('edit-options-operator-in', '', 'Operator In not exists');
    $this->assertNoFieldById('edit-options-operator-not-in', '', 'Operator Not In not exists');
    $this->assertNoFieldById('edit-options-value-page', '', 'Checkbox for Page not exists');
    $this->assertNoFieldById('edit-options-value-article', '', 'Checkbox for Article not exists');

    // Check that after click on 'Grouped Filters', a new button is shown to
    // add more items to the list.
    $this->helperButtonHasLabel('edit-options-group-info-add-group', t('Add another item'));

    // Create a grouped filter
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = array();
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';

    $edit["options[group_info][group_items][2][title]"] = 'Is Page';
    $edit["options[group_info][group_items][2][value][page]"] = TRUE;

    $edit["options[group_info][group_items][3][title]"] = 'Is Page and Article';
    $edit["options[group_info][group_items][3][value][article]"] = TRUE;
    $edit["options[group_info][group_items][3][value][page]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    // Select the empty operator, so the empty value should not trigger a form
    // error.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/body_value');
    $edit = array();
    $edit["options[group_info][group_items][1][title]"] = $this->randomMachineName();
    $edit["options[group_info][group_items][1][operator]"] = 'empty';
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertUrl('admin/structure/views/view/test_exposed_admin_ui/edit/default', array(), 'Validation did not run for the empty operator.');
    // Test the validation error message text is not shown.
    $this->assertNoText(t('The value is required if title for this item is defined.'));

    // Validate that all the titles are defined for each group
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit = array();
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = TRUE;

    // This should trigger an error
    $edit["options[group_info][group_items][2][title]"] = '';
    $edit["options[group_info][group_items][2][value][page]"] = TRUE;

    $edit["options[group_info][group_items][3][title]"] = 'Is Page and Article';
    $edit["options[group_info][group_items][3][value][article]"] = TRUE;
    $edit["options[group_info][group_items][3][value][page]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->assertRaw(t('The title is required if value for this item is defined.'), 'Group items should have a title');

    // Un-expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->drupalPostForm(NULL, array(), t('Hide filter'));

    // After Un-exposing the filter, Operator and Value should be shown again.
    $this->assertFieldById('edit-options-operator-in', '', 'Operator In exists after hide filter');
    $this->assertFieldById('edit-options-operator-not-in', '', 'Operator Not In exists after hide filter');
    $this->assertFieldById('edit-options-value-page', '', 'Checkbox for Page exists after hide filter');
    $this->assertFieldById('edit-options-value-article', '', 'Checkbox for Article exists after hide filter');

    // Click the Expose sort button.
    $edit = array();
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/sort/created', $edit, t('Expose sort'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide sort'));
    $this->assertFieldById('edit-options-expose-label', '', 'Make sure a label field is shown');
  }
}
