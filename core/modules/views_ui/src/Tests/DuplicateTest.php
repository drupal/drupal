<?php

namespace Drupal\views_ui\Tests;

/**
 * Tests the UI for view duplicate tool.
 *
 * @group views_ui
 */
class DuplicateTest extends UITestBase {

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Checks if duplicated view exists and has correct label.
   */
  public function testDuplicateView() {

    // Create random view.
    $random_view = $this->randomView();

    // Initialize array for duplicated view.
    $view = [];

    // Generate random label and id for new view.
    $view['label'] = $this->randomMachineName(255);
    $view['id'] = strtolower($this->randomMachineName(128));

    // Duplicate view.
    $this->drupalPostForm('admin/structure/views/view/' . $random_view['id'] . '/duplicate', $view, t('Duplicate'));

    // Assert that the page url is correct.
    $this->assertUrl('admin/structure/views/view/' . $view['id'], [], 'Make sure the view saving was successful and the browser got redirected to the edit page.');

    // Assert that the page title is correctly displayed.
    $this->assertText($view['label']);
  }

}
