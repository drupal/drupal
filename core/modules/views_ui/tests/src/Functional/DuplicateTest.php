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

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

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
    $view['id'] = $this->randomMachineName(128);

    // Duplicate view.
    $this->drupalGet('admin/structure/views/view/' . $random_view['id'] . '/duplicate');
    $this->submitForm($view, 'Duplicate');

    // Assert that the page URL is correct.
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $view['id']);

    // Assert that the page title is correctly displayed.
    $this->assertSession()->pageTextContains($view['label']);
  }

}
