<?php

namespace Drupal\Tests\block_content\Kernel\Migrate\d7;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of content blocks.
 *
 * @group block_content
 */
class MigrateCustomBlockTest extends MigrateDrupal7TestBase {

  protected static $modules = [
    'block_content',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd7_custom_block',
    ]);
  }

  /**
   * Tests migration of content blocks from Drupal 7 to Drupal 8.
   */
  public function testCustomBlockMigration() {
    $block = BlockContent::load(1);
    $this->assertInstanceOf(BlockContentInterface::class, $block);
    /** @var \Drupal\block_content\BlockContentInterface $block */
    $this->assertSame('Limerick', $block->label());

    $expected_body = "A fellow jumped off a high wall\r\nAnd had a most terrible fall\r\nHe went back to bed\r\nWith a bump on his head\r\nThat's why you don't jump off a wall";
    $this->assertSame($expected_body, $block->body->value);
    $this->assertSame('filtered_html', $block->body->format);
  }

}
