<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Layout Builder local tasks.
 *
 * @group layout_builder
 */
class LayoutBuilderLocalTaskTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the cacheability of local tasks with Layout Builder module installed.
   */
  public function testLocalTaskLayoutBuilderInstalledCacheability() {
    // Create only one bundle and do not enable layout builder on its display.
    $this->drupalCreateContentType([
      'type' => 'bundle_no_lb_display',
    ]);

    LayoutBuilderEntityViewDisplay::load('node.bundle_no_lb_display.default')
      ->disableLayoutBuilder()
      ->save();

    $node = $this->drupalCreateNode([
      'type' => 'bundle_no_lb_display',
    ]);

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));

    $this->drupalGet('node/' . $node->id());
    $assert_session->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests the cacheability of local tasks with multiple content types.
   */
  public function testLocalTaskMultipleContentTypesCacheability() {
    // Test when there are two content types, one with a display having Layout
    // Builder enabled with overrides, and another with display not having
    // Layout Builder enabled.
    $this->drupalCreateContentType([
      'type' => 'bundle_no_lb_display',
    ]);
    LayoutBuilderEntityViewDisplay::load('node.bundle_no_lb_display.default')
      ->disableLayoutBuilder()
      ->save();

    $node_without_lb = $this->drupalCreateNode([
      'type' => 'bundle_no_lb_display',
    ]);

    $this->drupalCreateContentType([
      'type' => 'bundle_with_overrides',
    ]);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_overrides.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $node_with_overrides = $this->drupalCreateNode([
      'type' => 'bundle_with_overrides',
    ]);

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));

    $this->drupalGet('node/' . $node_without_lb->id());
    $assert_session->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('node/' . $node_with_overrides->id());
    $assert_session->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
    $assert_session->statusCodeEquals(200);
  }

}
