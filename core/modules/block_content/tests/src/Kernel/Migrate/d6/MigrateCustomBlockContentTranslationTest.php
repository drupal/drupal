<?php

namespace Drupal\Tests\block_content\Kernel\Migrate\d6;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of i18n custom block strings.
 *
 * @group migrate_drupal_6
 */
class MigrateCustomBlockContentTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'content_translation',
    'language',
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
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd6_custom_block',
      'd6_custom_block_translation',
    ]);
  }

  /**
   * Tests the Drupal 6 i18n custom block strings to Drupal 8 migration.
   */
  public function testCustomBlockContentTranslation() {
    /** @var \Drupal\block_content\Entity\BlockContent $block */
    $block = BlockContent::load(1)->getTranslation('fr');
    $this->assertSame('fr - Static Block', $block->label());
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $block->getChangedTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('fr', $block->language()->getId());
    $this->assertSame('<h3>fr - My first custom block body</h3>', $block->body->value);
    $this->assertSame('full_html', $block->body->format);

    $block = $block->getTranslation('zu');
    $this->assertSame('My block 1', $block->label());
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $block->getChangedTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('zu', $block->language()->getId());
    $this->assertSame('<h3>zu - My first custom block body</h3>', $block->body->value);
    $this->assertSame('full_html', $block->body->format);

    $block = BlockContent::load(2)->getTranslation('fr');
    $this->assertSame('Encore un bloc statique', $block->label());
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $block->getChangedTime());
    $this->assertLessThanOrEqual(time(), $block->getChangedTime());
    $this->assertSame('fr', $block->language()->getId());
    $this->assertSame('Nom de vocabulaire beaucoup plus long que trente-deux caractères', $block->body->value);
    $this->assertSame('full_html', $block->body->format);
  }

}
