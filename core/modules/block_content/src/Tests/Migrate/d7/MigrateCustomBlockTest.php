<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Migrate\d7\MigrateCustomBlockTest.
 */

namespace Drupal\block_content\Tests\Migrate\d7;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of custom blocks.
 *
 * @group block_content
 */
class MigrateCustomBlockTest extends MigrateDrupal7TestBase {

  public static $modules = array(
    'block_content',
    'filter',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->installEntitySchema('block_content');

    $this->executeMigrations([
      'd7_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd7_custom_block',
    ]);
  }

  /**
   * Tests migration of custom blocks from Drupal 7 to Drupal 8.
   */
  public function testCustomBlockMigration() {
    $block = BlockContent::load(1);
    $this->assertTrue($block instanceof BlockContentInterface);
    /** @var \Drupal\block_content\BlockContentInterface $block */
    $this->assertIdentical('Limerick', $block->label());

    $expected_body = "A fellow jumped off a high wall\r\nAnd had a most terrible fall\r\nHe went back to bed\r\nWith a bump on his head\r\nThat's why you don't jump off a wall";
    $this->assertIdentical($expected_body, $block->body->value);
    $this->assertIdentical('filtered_html', $block->body->format);
  }

}
