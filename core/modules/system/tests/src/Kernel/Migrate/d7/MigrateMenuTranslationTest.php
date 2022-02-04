<?php

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests menu translation migration.
 *
 * @group migrate_drupal_7
 */
class MigrateMenuTranslationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'language',
    'locale',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('locale',
      ['locales_source', 'locales_target', 'locales_location']);
    $this->executeMigrations([
      'language',
      'd7_menu',
      'd7_menu_translation',
    ]);
  }

  /**
   * Tests migration of menu translations.
   */
  public function testMenuTranslation() {
    $language_manager = \Drupal::service('language_manager');

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'system.menu.main');
    $this->assertSame('is - Main menu', $config_translation->get('label'));
    $this->assertSame('is - Main menu description', $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.menu.main');
    $this->assertSame('fr - Main menu', $config_translation->get('label'));
    $this->assertSame('fr - Main menu description', $config_translation->get('description'));

    // Translate and localize menu.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.menu.menu-test-menu');
    $this->assertSame('fr - Test menu description', $config_translation->get('description'));

    // No translations for fixed language menu.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'menu-fixedlang');
    $this->assertNull($config_translation->get('description'));
    $this->assertNull($config_translation->get('label'));
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'menu-fixedlang');
    $this->assertNull($config_translation->get('description'));
    $this->assertNull($config_translation->get('label'));

    // Test rollback.
    $this->migration = $this->getMigration("d7_menu_translation");
    (new MigrateExecutable($this->migration, $this))->rollback();

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'system.menu.main');
    $this->assertNull($config_translation->get('description'));
    $this->assertNull($config_translation->get('label'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.menu.main');
    $this->assertNull($config_translation->get('description'));
    $this->assertNull($config_translation->get('label'));

    // Translate and localize menu.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'system.menu.menu-test-menu');
    $this->assertNull($config_translation->get('description'));
  }

}
