<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldDropButtonTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the dropbutton field handler.
 *
 * @group views
 * @see \Drupal\system\Plugin\views\field\Dropbutton
 */
class FieldDropButtonTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_dropbutton');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['access content overview', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests dropbutton field.
   */
  public function testDropbutton() {
    // Create some test nodes.
    $nodes = array();
    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode();
    }

    $this->drupalGet('test-dropbutton');
    foreach ($nodes as $node) {
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => '/node/' . $node->id(), ':title' => $node->label()));
      $this->assertEqual(count($result), 1, 'Just one node title link was found.');
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => '/node/' . $node->id(), ':title' => t('Custom Text')));
      $this->assertEqual(count($result), 1, 'Just one custom link was found.');
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
