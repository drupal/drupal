<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

/**
 * Tests block content theme suggestions.
 *
 * @group block_content
 */
class BlockContentThemeSuggestionsTest extends BlockContentTestBase {

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_extra_field';

  /**
   * The UUID for a block content entity.
   */
  protected string $uuid = 'b22c881a-bcfd-4d0c-a41d-3573327705df';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'layout_builder',
    'node',
    'block_content_theme_suggestions_test',
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

    // Create a block with a known UUID.
    $block = $this->createBlockContent('Example block!', 'basic', FALSE);
    $block->set('uuid', $this->uuid);
    $block->save();
  }

  /**
   * Test suggestions for content blocks.
   */
  public function testBlockContentThemeSuggestionsContent(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('block_content:' . $this->uuid);
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Example block!');
    $this->assertSession()->pageTextContainsOnce('I am a block content template for a specific bundle and view mode!');
  }

  /**
   * Test suggestions for content blocks within extra fields blocks.
   */
  public function testBlockContentThemeSuggestionsExtraField(): void {
    // Extra field blocks are a block plugin provided by layout builder, so
    // enable layouts for the test bundle and view a node of that bundle.
    // A test module injects an extra field referencing a block content entity.
    // @see block_content_theme_suggestions_test.module
    // @see \Drupal\block_content_theme_suggestions_test\Hook\BlockContentThemeSuggestionsTestHooks
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));
    $this->createContentType(['type' => 'bundle_with_extra_field']);
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $node = $this->createNode([
      'type' => 'bundle_with_extra_field',
      'title' => 'The first node title',
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Example block!');
    $this->assertSession()->pageTextContains('I am a block content template for a specific bundle and view mode!');
  }

}
