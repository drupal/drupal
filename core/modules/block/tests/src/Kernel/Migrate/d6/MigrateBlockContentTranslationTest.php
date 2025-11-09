<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel\Migrate\d6;

use Drupal\block\Hook\BlockHooks;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests migration of i18n block translations.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateBlockContentTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'config_translation',
    'language',
    'locale',
    'path_alias',
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
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'block_content_body_field_storage',
      'd6_menu',
      'd6_custom_block',
      'd6_user_role',
      'd6_block',
      'd6_block_translation',
    ]);
    $blockRebuild = new BlockHooks();
    $blockRebuild->rebuild();
  }

  /**
   * Tests the migration of block title translation.
   */
  public function testBlockContentTranslation(): void {
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    $config = $language_manager->getLanguageConfigOverride('zu', 'block.block.user_1');
    $this->assertSame('zu - Navigation', $config->get('settings.label'));
  }

}
