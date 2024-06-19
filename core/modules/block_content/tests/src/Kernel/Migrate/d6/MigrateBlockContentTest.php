<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Migrate\d6;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade content blocks.
 *
 * @group migrate_drupal_6
 */
class MigrateBlockContentTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(['block_content']);

    $this->executeMigrations([
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd6_custom_block',
    ]);
  }

  /**
   * Tests the Drupal 6 content block to Drupal 8 migration.
   */
  public function testBlockMigration(): void {
    /** @var \Drupal\block_content\Entity\BlockContent $block */
    $block = BlockContent::load(1);
    $this->assertSame('My block 1', $block->label());
    $requestTime = \Drupal::time()->getRequestTime();
    $this->assertGreaterThanOrEqual($requestTime, (int) $block->getChangedTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('en', $block->language()->getId());
    $this->assertSame('<h3>My first content block body</h3>', $block->body->value);
    $this->assertSame('full_html', $block->body->format);

    $block = BlockContent::load(2);
    $this->assertSame('My block 2', $block->label());
    $this->assertGreaterThanOrEqual($requestTime, (int) $block->getChangedTime());
    $this->assertGreaterThanOrEqual($requestTime, (int) $block->getChangedTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('en', $block->language()->getId());
    $this->assertSame('<h3>My second content block body</h3>', $block->body->value);
    $this->assertSame('full_html', $block->body->format);
  }

}
