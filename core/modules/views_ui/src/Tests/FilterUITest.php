<?php

/**
 * @file
 * Contains \Drupal\views\Tests\FilterUITest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests for the filters from the UI.
 *
 * @group views_ui
 */
class FilterUITest extends ViewTestBase {


  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_in_operator_ui', 'test_filter_groups');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(array('type' => 'page'));
    $this->enableViewsTestModule();
  }

  /**
   * Tests that an option for a filter is saved as expected from the UI.
   */
  public function testFilterInOperatorUi() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';
    $this->drupalGet($path);
    // Verifies that "Limit list to selected items" option is not selected.
    $this->assertFieldByName('options[expose][reduce]', FALSE);

    // Select "Limit list to selected items" option and apply.
    $edit = array(
      'options[expose][reduce]' => TRUE,
    );
    $this->drupalPostForm($path, $edit, t('Apply'));

    // Verifies that the option was saved as expected.
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', TRUE);
  }

  /**
   * Tests the filters from the UI.
   */
  public function testFiltersUI() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/test_filter_groups');

    $this->assertLink('Content: Node ID (= 1)', 0, 'Content: Node ID (= 1) link appears correctly.');

    // Tests that we can create a new filter group from UI.
    $this->drupalGet('admin/structure/views/nojs/rearrange-filter/test_filter_groups/page');
    $this->assertNoRaw('<span>Group 3</span>', 'Group 3 has not been added yet.');

    // Create 2 new groups.
    $this->drupalPostForm(NULL, [], t('Create new filter group'));
    $this->drupalPostForm(NULL, [], t('Create new filter group'));

    // Remove the new group 3.
    $this->drupalPostForm(NULL, [], t('Remove group 3'));

    // Verify that the group 4 is now named as 3.
    $this->assertRaw('<span>Group 3</span>', 'Group 3 still exists.');

    // Remove the group 3 again.
    $this->drupalPostForm(NULL, [], t('Remove group 3'));

    // Group 3 now does not exist.
    $this->assertNoRaw('<span>Group 3</span>', 'Group 3 has not been added yet.');
  }

}
