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
  protected static $modules = [
    'node',
    'text',
    'aggregator',
    'book',
    'block',
    'comment',
    'filter',
    'forum',
    'views',
    'block_content',
    'config_translation',
    'language',
    'locale',
    'path_alias',
    'statistics',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(['block']);
    $this->installConfig(['block_content']);

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
