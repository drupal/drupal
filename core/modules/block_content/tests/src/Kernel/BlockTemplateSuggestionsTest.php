<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\block_content\Hook\BlockContentHooks;

/**
 * Tests the block_content_theme_suggestions_block() function.
 *
 * @group block_content
 */
class BlockTemplateSuggestionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'block',
    'block_content',
    'system',
  ];

  /**
   * The BlockContent entity used for testing.
   *
   * @var \Drupal\block_content\Entity\BlockContent
   */
  protected $blockContent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('block_content');

    // Create a block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'test_block',
      'label' => 'A test block type',
      'description' => "Provides a test block type.",
    ]);
    $block_content_type->save();

    $this->blockContent = BlockContent::create([
      'info' => 'The Test Block',
      'type' => 'test_block',
    ]);
    $this->blockContent->save();
  }

  /**
   * Tests template suggestions from block_content_theme_suggestions_block().
   */
  public function testBlockThemeHookSuggestions(): void {
    // Create a block using a block_content plugin.
    $block = Block::create([
      'plugin' => 'block_content:' . $this->blockContent->uuid(),
      'region' => 'footer',
      'id' => 'machine_name',
    ]);

    $variables['elements']['#id'] = $block->id();
    $variables['elements']['#configuration']['provider'] = 'block_content';
    $variables['elements']['#configuration']['view_mode'] = 'full';
    $variables['elements']['content']['#block_content'] = $this->blockContent;
    $suggestions = [];
    $suggestions[] = 'block__block_content__' . $block->uuid();
    $blockTemplateSuggestionsAlter = new BlockContentHooks();
    $blockTemplateSuggestionsAlter->themeSuggestionsBlockAlter($suggestions, $variables);

    $this->assertSame([
      'block__block_content__' . $block->uuid(),
      'block__block_content__view__full',
      'block__block_content__type__test_block',
      'block__block_content__view_type__test_block__full',
      'block__block_content__id__machine_name',
      'block__block_content__id_view__machine_name__full',
    ], $suggestions);
  }

}
