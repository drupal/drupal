<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the boolean filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\BooleanOperator
 */
class FilterBooleanWebTest extends UITestBase {

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
   * Tests the filter boolean UI.
   */
  public function testFilterBooleanUI() {
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_view/default/filter', ['name[views_test_data.status]' => TRUE], t('Add and configure @handler', ['@handler' => t('filter criteria')]));

    // Check the field widget label. 'title' should be used as a fallback.
    $result = $this->cssSelect('#edit-options-value--wrapper legend span');
    $this->assertEqual($result[0]->getHtml(), 'Status');

    // Ensure that the operator and the filter value are displayed using correct
    // layout.
    $this->assertSession()->elementExists('css', '.views-left-30 .form-item-options-operator');
    $this->assertSession()->elementExists('css', '.views-right-70 .form-item-options-value');

    $this->drupalPostForm(NULL, [], t('Expose filter'));
    $this->drupalPostForm(NULL, [], t('Grouped filters'));

    $edit = [];
    $edit['options[group_info][group_items][1][title]'] = 'Published';
    $edit['options[group_info][group_items][1][operator]'] = '=';
    $edit['options[group_info][group_items][1][value]'] = 1;
    $edit['options[group_info][group_items][2][title]'] = 'Not published';
    $edit['options[group_info][group_items][2][operator]'] = '=';
    $edit['options[group_info][group_items][2][value]'] = 0;
    $edit['options[group_info][group_items][3][title]'] = 'Not published2';
    $edit['options[group_info][group_items][3][operator]'] = '!=';
    $edit['options[group_info][group_items][3][value]'] = 1;

    $this->drupalPostForm(NULL, $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/status');

    $result = $this->xpath('//input[@name="options[group_info][group_items][1][value]"]');
    $this->assertEqual($result[1]->getAttribute('checked'), 'checked');
    $result = $this->xpath('//input[@name="options[group_info][group_items][2][value]"]');
    $this->assertEqual($result[2]->getAttribute('checked'), 'checked');
    $result = $this->xpath('//input[@name="options[group_info][group_items][3][value]"]');
    $this->assertEqual($result[1]->getAttribute('checked'), 'checked');

    // Test that there is a remove link for each group.
    $this->assertCount(3, $this->cssSelect('a.views-remove-link'));

    // Test selecting a default and removing an item.
    $edit = [];
    $edit['options[group_info][default_group]'] = 2;
    $edit['options[group_info][group_items][3][remove]'] = 1;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/status');
    $this->assertFieldByName('options[group_info][default_group]', 2, 'Second item was set as the default.');
    $this->assertNoField('options[group_info][group_items][3][remove]', 'Third item was removed.');
  }

}
