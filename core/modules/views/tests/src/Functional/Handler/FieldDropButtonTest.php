<?php

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
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

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
  public function testDropbutton() {
    // Create some test nodes.
    $nodes = [];
    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode();
    }

    $this->drupalGet('test-dropbutton');
    foreach ($nodes as $node) {
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', [':path' => '/node/' . $node->id(), ':title' => $node->label()]);
      $this->assertCount(1, $result, 'Just one node title link was found.');
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', [':path' => '/node/' . $node->id(), ':title' => 'Custom Text']);
      $this->assertCount(1, $result, 'Just one custom link was found.');
    }

    // Check if the dropbutton.js library is available.
    $this->drupalGet('admin/content');
    $this->assertRaw('dropbutton.js');
    // Check if the dropbutton.js library is available on a cached page to
    // ensure that bubbleable metadata is not lost in the views render workflow.
    $this->drupalGet('admin/content');
    $this->assertRaw('dropbutton.js');
  }

}
