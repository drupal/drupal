<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Component\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block_content\Traits\BlockContentCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests cache metadata for BlockContentBlock plugin.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentCacheTest extends KernelTestBase {

  use BlockContentCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');

    $this->createBlockContentType(['id' => 'basic', 'label' => 'Basic']);
  }

  /**
   * Tests the cache tags for BlockContentBlock.
   */
  public function testBlockContentBlockCacheData(): void {
    $block_content = $this->createBlockContent('Cache test');

    $block = Block::create([
      'plugin' => 'block_content' . PluginBase::DERIVATIVE_SEPARATOR . $block_content->uuid(),
      'region' => 'content',
      'id' => 'cache_test',
      'theme' => 'stark',
    ]);

    $plugin = $block->getPlugin();
    $this->assertCount(0, \array_diff($block_content->getCacheContexts(), $plugin->getCacheContexts()));
    $this->assertSame($block_content->getCacheMaxAge(), $plugin->getCacheMaxAge());
    $this->assertCount(0, \array_diff($block_content->getCacheTags(), $plugin->getCacheTags()));
  }

}
