<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests exposed forms with combined filter functionality.
 *
 * @group views
 */
class CombinedFilterExposedFormTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['basic_page_summary_test'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'page']);

    // Create some random nodes.
    $nodes[] = [
      'type' => 'page',
      'title' => 'Hello World Node 1',
      'body' => ['value' => 'Body value', 'summary' => 'Summary value Drupal', 'format' => 'plain_text'],
    ];
    $nodes[] = [
      'type' => 'page',
      'title' => 'Hello World Node 2',
      'body' => ['value' => 'Body value', 'summary' => 'Summary value', 'format' => 'plain_text'],
    ];
    foreach ($nodes as $node) {
      $this->drupalCreateNode($node);
    }
  }

  /**
   * Tests the exposed form filter.
   */
  public function testExposedFormWithCombinedFilter() {
    // Test the submit button value defaults to 'Apply'.
    $this->drupalGet('basic-page-summary-test');
    $this->assertSession()->pageTextContains('Hello World Node 1');
    $this->assertSession()->pageTextContains('Hello World Node 2');
    $edit = [
      'combine' => 'Drupal',
    ];
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello World Node 1');
    $this->assertSession()->pageTextNotContains('Hello World Node 2');
  }

}
