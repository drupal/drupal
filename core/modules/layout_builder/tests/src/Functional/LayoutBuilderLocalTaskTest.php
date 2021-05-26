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
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create two content types and a node for each.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createContentType([
      'type' => 'bundle_without_section_field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createNode([
      'type' => 'bundle_without_section_field',
    ]);

    // Enable layout builder overrides.
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests the cacheability of local tasks.
   */
  public function testLocalTaskCacheability() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ]));

    $this->drupalGet('node/1');
    $assert_session->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
    $this->drupalGet('node/2');
    $assert_session->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
  }

}
