<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the UI for view duplicate tool.
 *
 * @group views_ui
 */
class DuplicateTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->placeBlock('page_title_block');
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
    $this->drupalPostForm('admin/structure/views/view/' . $random_view['id'] . '/duplicate', $view, 'Duplicate');

    // Assert that the page url is correct.
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $view['id']);

    // Assert that the page title is correctly displayed.
    $this->assertText($view['label']);
  }

}
