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
  }

}
