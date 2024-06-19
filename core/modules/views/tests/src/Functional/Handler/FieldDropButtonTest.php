<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the dropbutton field handler.
 *
 * @group views
 * @see \Drupal\system\Plugin\views\field\Dropbutton
 */
class FieldDropButtonTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_dropbutton'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $admin_user = $this->drupalCreateUser([
      'access content overview',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests dropbutton field.
   */
  public function testDropbutton(): void {
    // Create some test nodes.
    $nodes = [];
    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode();
    }

    $this->drupalGet('test-dropbutton');
    foreach ($nodes as $node) {
      // Test that only one node title link was found.
      $this->assertSession()->elementsCount('xpath', "//ul[contains(@class, dropbutton)]/li/a[contains(@href, '/node/{$node->id()}') and text()='{$node->label()}']", 1);
      // Test that only one custom link was found.
      $this->assertSession()->elementsCount('xpath', "//ul[contains(@class, dropbutton)]/li/a[contains(@href, '/node/{$node->id()}') and text()='Custom Text']", 1);
    }

    // Check if the dropbutton.js library is available.
    $this->drupalGet('admin/content');
    $this->assertSession()->responseContains('dropbutton.js');
    // Check if the dropbutton.js library is available on a cached page to
    // ensure that bubbleable metadata is not lost in the views render workflow.
    $this->drupalGet('admin/content');
    $this->assertSession()->responseContains('dropbutton.js');
  }

}
