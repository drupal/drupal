<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

/**
 * Tests the View HTML List style.
 *
 * @group views
 */
class ViewStyleHtmlListTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static array $testViews = ['test_style_html_list_ordered'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 1; $i < 10; $i++) {
      $this->drupalCreateNode([
        'title' => 'Node ' . $i,
      ]);
    }
  }

  /**
   * Tests ordered list HTML list.
   */
  public function testOrderedList(): void {
    $this->drupalGet('test-style-html-list-ordered');

    // Verify we see the first 2 nodes.
    $this->assertSession()->pageTextContains("Node 1");
    $this->assertSession()->pageTextContains("Node 2");
    $this->assertSession()->elementExists('css', 'ol[start=1]');

    $this->drupalGet('test-style-html-list-ordered', ['query' => ['page' => '1']]);

    // Verify we see the next 2 nodes.
    $this->assertSession()->pageTextContains("Node 3");
    $this->assertSession()->pageTextContains("Node 4");
    $this->assertSession()->elementExists('css', 'ol[start=3]');
  }

}
