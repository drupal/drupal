<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ExposedFormTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests exposed forms.
 */
class ExposedFormTest extends ViewsSqlTest {

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Exposed forms',
      'description' => 'Test exposed forms functionality.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Tests, whether and how the reset button can be renamed.
   */
  public function testRenameResetButton() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }
    // Look at the page and check the label "reset".
    $this->drupalGet('test_rename_reset_button');
    // Rename the label of the reset button.
    $view = views_get_view('test_rename_reset_button');
    $view->set_display('default');

    $exposed_form = $view->display_handler->get_option('exposed_form');
    $exposed_form['options']['reset_button_label'] = $expected_label = $this->randomName();
    $exposed_form['options']['reset_button'] = TRUE;
    $view->display_handler->set_option('exposed_form', $exposed_form);
    $view->save();

    views_invalidate_cache();

    // Look whether ther reset button label changed.
    $this->drupalGet('test_rename_reset_button');

    $this->helperButtonHasLabel('edit-reset', $expected_label);
  }

  /**
   * Tests the admin interface of exposed filter and sort items.
   */
  function testExposedAdminUi() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);
    menu_router_rebuild();
    $edit = array();

    $this->drupalGet('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/filter/type');
    // Be sure that the button is called exposed.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose filter'));

    // Click the Expose filter button.
    $this->drupalPost('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/filter/type', $edit, t('Expose filter'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide filter'));
    // Check the label of the grouped exposed button
    $this->helperButtonHasLabel('edit-options-group-button-button', t('Grouped filters'));

    // Check the validations of the filter handler.
    $edit = array();
    $edit['options[expose][identifier]'] = '';
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->assertText(t('The identifier is required if the filter is exposed.'));

    $edit = array();
    $edit['options[expose][identifier]'] = 'value';
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->assertText(t('This identifier is not allowed.'));

    // Now check the sort criteria.
    $this->drupalGet('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/sort/created');
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Expose sort'));
    $this->assertNoFieldById('edit-options-expose-label', '', t('Make sure no label field is shown'));

    // Click the Grouped Filters button.
    $this->drupalGet('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/filter/type');
    $this->drupalPost(NULL, array(), t('Grouped filters'));
    // Check that after click on 'Grouped Filters', a new button is shown to
    // add more items to the list.
    $this->helperButtonHasLabel('edit-options-group-info-add-group', t('Add another item'));

    // Create a grouped filter
    $this->drupalGet('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/filter/type');
    $edit = array();
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = 'article';

    $edit["options[group_info][group_items][2][title]"] = 'Is Page';
    $edit["options[group_info][group_items][2][value][page]"] = TRUE;

    $edit["options[group_info][group_items][3][title]"] = 'Is Page and Article';
    $edit["options[group_info][group_items][3][value][article]"] = TRUE;
    $edit["options[group_info][group_items][3][value][page]"] = TRUE;
    $this->drupalPost(NULL, $edit, t('Apply'));

    // Validate that all the titles are defined for each group
    $this->drupalGet('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/filter/type');
    $edit = array();
    $edit["options[group_info][group_items][1][title]"] = 'Is Article';
    $edit["options[group_info][group_items][1][value][article]"] = TRUE;

    // This should trigger an error
    $edit["options[group_info][group_items][2][title]"] = '';
    $edit["options[group_info][group_items][2][value][page]"] = TRUE;

    $edit["options[group_info][group_items][3][title]"] = 'Is Page and Article';
    $edit["options[group_info][group_items][3][value][article]"] = TRUE;
    $edit["options[group_info][group_items][3][value][page]"] = TRUE;
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->assertRaw(t('The title is required if value for this item is defined.'), t('Group items should have a title'));

    // Click the Expose sort button.
    $edit = array();
    $this->drupalPost('admin/structure/views/nojs/config-item/test_exposed_admin_ui/default/sort/created', $edit, t('Expose sort'));
    // Check the label of the expose button.
    $this->helperButtonHasLabel('edit-options-expose-button-button', t('Hide sort'));
    $this->assertFieldById('edit-options-expose-label', '', t('Make sure a label field is shown'));
  }

}
