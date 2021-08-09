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
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';
    $this->drupalGet($path);
    // Verifies that "Limit list to selected items" option is not selected.
    $this->assertSession()->fieldValueEquals('options[expose][reduce]', FALSE);

    // Select "Limit list to selected items" option and apply.
    $edit = [
      'options[expose][reduce]' => TRUE,
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');

    // Verifies that the option was saved as expected.
    $this->drupalGet($path);
    $this->assertSession()->fieldValueEquals('options[expose][reduce]', TRUE);
  }

  /**
   * Tests the filters from the UI.
   */
  public function testFiltersUI() {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/test_filter_groups');

    $this->assertSession()->linkExists('Content: ID (= 1)', 0, 'Content: ID (= 1) link appears correctly.');

    // Tests that we can create a new filter group from UI.
    $this->drupalGet('admin/structure/views/nojs/rearrange-filter/test_filter_groups/page');
    $this->assertSession()->elementNotExists('xpath', '//span[text()="Group 3"]');

    // Create 2 new groups.
    $this->submitForm([], 'Create new filter group');
    $this->submitForm([], 'Create new filter group');

    // Remove the new group 3.
    $this->submitForm([], 'Remove group 3');

    // Verify that the group 4 is now named as 3.
    $this->assertRaw('<span>Group 3</span>');

    // Remove the group 3 again.
    $this->submitForm([], 'Remove group 3');

    // Group 3 now does not exist.
    $this->assertSession()->elementNotExists('xpath', '//span[text()="Group 3"]');
  }

  /**
   * Tests the identifier settings and restrictions.
   */
  public function testFilterIdentifier() {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';

    // Set an empty identifier.
    $edit = [
      'options[expose][identifier]' => '',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('The identifier is required if the filter is exposed.');

    // Set the identifier to 'value'.
    $edit = [
      'options[expose][identifier]' => 'value',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('This identifier is not allowed.');

    // Try a few restricted values for the identifier.
    foreach (['value value', 'value^value'] as $identifier) {
      $edit = [
        'options[expose][identifier]' => $identifier,
      ];
      $this->drupalGet($path);
      $this->submitForm($edit, 'Apply');
      $this->assertSession()->pageTextContains('This identifier has illegal characters.');
    }
  }

}
