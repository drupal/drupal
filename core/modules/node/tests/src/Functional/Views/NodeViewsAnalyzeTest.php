<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views;

/**
 * Tests node_views_analyze().
 *
 * @group node
 */
class NodeViewsAnalyzeTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui', 'node_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_views_analyze'];

  /**
   * Tests the implementation of node_views_analyze().
   */
  public function testNodeViewsAnalyze(): void {
    // Create user with permission to view analyze message on views_ui.
    $admin_user = $this->createUser(['administer views']);

    $this->drupalLogin($admin_user);

    // Access to views analyze page.
    $this->drupalGet('admin/structure/views/nojs/analyze/test_node_views_analyze/page_1');

    // Should return 200 with correct permission.
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('has set node/% as path. This will not produce what you want. If you want to have multiple versions of the node view, use Layout Builder.');
  }

}
