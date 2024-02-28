<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Layout Builder UI.
 */
class LayoutBuilderTestBase extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'views',
    'layout_builder',
    'layout_builder_views_test',
    'layout_test',
    'block',
    'block_test',
    'contextual',
    'node',
    'layout_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);
  }

}
