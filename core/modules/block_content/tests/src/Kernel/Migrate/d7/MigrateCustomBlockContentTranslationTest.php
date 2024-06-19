<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Migrate\d7;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of i18n content block strings.
 *
 * @group migrate_drupal_7
 */
class MigrateCustomBlockContentTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'content_translation',
    'filter',
    'language',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(['block_content']);
    $this->executeMigrations([
      'language',
      'd7_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd7_custom_block',
      'd7_custom_block_translation',
    ]);
  }

  /**
   * Tests the Drupal 7 i18n content block strings to Drupal 8 migration.
   */
  public function testCustomBlockContentTranslation(): void {
    /** @var \Drupal\block_content\Entity\BlockContent $block */
    $block = BlockContent::load(1)->getTranslation('fr');
    $this->assertSame('fr - Mildly amusing limerick of the day', $block->label());
    $this->assertGreaterThanOrEqual($block->getChangedTime(), \Drupal::time()->getRequestTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('fr', $block->language()->getId());
    $translation = "fr - A fellow jumped off a high wall\r\nAnd had a most terrible fall\r\nHe went back to bed\r\nWith a bump on his head\r\nThat's why you don't jump off a wall";
    $this->assertSame($translation, $block->body->value);
    $this->assertSame('filtered_html', $block->body->format);

    $block = $block->getTranslation('is');
    $this->assertSame('is - Mildly amusing limerick of the day', $block->label());
    $this->assertGreaterThanOrEqual($block->getChangedTime(), \Drupal::time()->getRequestTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('is', $block->language()->getId());
    $text = "A fellow jumped off a high wall\r\nAnd had a most terrible fall\r\nHe went back to bed\r\nWith a bump on his head\r\nThat's why you don't jump off a wall";
    $this->assertSame($text, $block->body->value);
    $this->assertSame('filtered_html', $block->body->format);
  }

}
