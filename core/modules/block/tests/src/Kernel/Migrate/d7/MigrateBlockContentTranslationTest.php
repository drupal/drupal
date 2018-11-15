<?php

namespace Drupal\Tests\block\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of i18n block translations.
 *
 * @group migrate_drupal_7
 */
class MigrateBlockContentTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'text',
    'aggregator',
    'book',
    'block',
    'comment',
    'forum',
    'views',
    'block_content',
    'config_translation',
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
      'language',
      'd7_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd7_custom_block',
      'd7_user_role',
      'd7_block',
      'd7_block_translation',
    ]);
    block_rebuild();
  }

  /**
   * Tests the migration of block title translation.
   */
  public function testBlockContentTranslation() {
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    $config = $language_manager->getLanguageConfigOverride('fr', 'block.block.bartik_user_login');
    $this->assertSame('fr - User login title', $config->get('settings.label'));
  }

}
