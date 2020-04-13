<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests for the filters from the UI.
 *
 * @group views_ui
 */
class FilterUITest extends UITestBase {


  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_in_operator_ui', 'test_filter_groups'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests that an option for a filter is saved as expected from the UI.
   */
  public function testFilterInOperatorUi() {
    $admin_user = $this->drupalCreateUser(['administer views', 'administer site configuration']);
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';
    $this->drupalGet($path);
    // Verifies that "Limit list to selected items" option is not selected.
    $this->assertFieldByName('options[expose][reduce]', FALSE);

    // Select "Limit list to selected items" option and apply.
    $edit = [
      'options[expose][reduce]' => TRUE,
    ];
    $this->drupalPostForm($path, $edit, t('Apply'));

    // Verifies that the option was saved as expected.
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', TRUE);
  }

  /**
   * Tests the filters from the UI.
   */
  public function testFiltersUI() {
    $admin_user = $this->drupalCreateUser(['administer views', 'administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/test_filter_groups');

    $this->assertLink('Content: ID (= 1)', 0, 'Content: ID (= 1) link appears correctly.');

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

  /**
   * Tests the identifier settings and restrictions.
   */
  public function testFilterIdentifier() {
    $admin_user = $this->drupalCreateUser(['administer views', 'administer site configuration']);
    $this->drupalLogin($admin_user);
    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';

    // Set an empty identifier.
    $edit = [
      'options[expose][identifier]' => '',
    ];
    $this->drupalPostForm($path, $edit, t('Apply'));
    $this->assertText('The identifier is required if the filter is exposed.');

    // Set the identifier to 'value'.
    $edit = [
      'options[expose][identifier]' => 'value',
    ];
    $this->drupalPostForm($path, $edit, t('Apply'));
    $this->assertText('This identifier is not allowed.');

    // Try a few restricted values for the identifier.
    foreach (['value value', 'value^value'] as $identifier) {
      $edit = [
        'options[expose][identifier]' => $identifier,
      ];
      $this->drupalPostForm($path, $edit, t('Apply'));
      $this->assertText('This identifier has illegal characters.');
    }
  }

}
