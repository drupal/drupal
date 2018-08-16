<?php

namespace Drupal\Tests\block\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of i18n block translations.
 *
 * @group migrate_drupal_6
 */
class MigrateBlockContentTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'aggregator',
    'book',
    'block',
    'comment',
    'forum',
    'views',
    'block_content',
    'content_translation',
    'language',
    'statistics',
    'taxonomy',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['block']);
    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');

    $this->executeMigrations([
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd6_custom_block',
      'd6_user_role',
      'd6_block',
      'd6_block_translation',
    ]);
    block_rebuild();
  }

  /**
   * Tests the migration of block title translation.
   */
  public function testBlockContentTranslation() {
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    $config = $language_manager->getLanguageConfigOverride('zu', 'block.block.user_1');
    $this->assertSame('zu - Navigation', $config->get('settings.label'));
  }

}
