<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\block\Hook\BlockHooks;

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
    'block',
    'comment',
    'filter',
    'views',
    'block_content',
    'config_translation',
    'language',
    'locale',
    'path_alias',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['block']);
    $this->installConfig(['block_content']);
    $this->container->get('theme_installer')->install(['stark']);

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
    $blockRebuild = new BlockHooks();
    $blockRebuild->rebuild();
  }

  /**
   * Tests the migration of block title translation.
   */
  public function testBlockContentTranslation(): void {
    // @todo Skipped due to frequent random test failures.
    // See https://www.drupal.org/project/drupal/issues/3389365
    $this->markTestSkipped();
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    $config = $language_manager->getLanguageConfigOverride('fr', 'block.block.bartik_user_login');
    $this->assertSame('fr - User login title', $config->get('settings.label'));
  }

}
